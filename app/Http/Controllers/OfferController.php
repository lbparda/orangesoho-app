<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Discount;
use App\Models\Offer;
use App\Models\Package;
use App\Models\Terminal;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Client;
use Illuminate\Http\Request; // Importación de Request
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail; // Asegúrate que esta línea existe
// use App\Mail\OfferPdfMail; // Descomenta si usas un Mailable
use App\Models\O2oDiscount; // Asegúrate de que esta importación existe

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // --- INICIO MODIFICACIÓN BENEFICIOS (Cargar 'benefits' en index) ---
        $query = Offer::with(['package', 'user.team','client', 'benefits.addon'])->latest();
        // --- FIN MODIFICACIÓN BENEFICIOS ---

        if ($user->isManager()) { 
            if ($user->team_id) {
                $teamMemberIds = User::where('team_id', $user->team_id)->pluck('id');
                $query->whereIn('user_id', $teamMemberIds);
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif($user->role === 'user') { 
             $query->where('user_id', $user->id);
        }

        return Inertia::render('Offers/Index', [
            'offers' => $query->paginate(10)
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        // --- INICIO MODIFICACIÓN BENEFICIOS ---
        // 1. Cargar todos los addons (incluyendo productos-beneficio como MS 365, Disney+, etc.)
        $allAddons = Addon::all(); 

        // 2. Cargar paquetes con sus reglas de beneficio y el addon al que apuntan
        $packages = Package::with([
            'addons', 
            'o2oDiscounts', 
            'terminals',
            'benefits.addon' // Carga la regla Y el producto al que apunta
        ])->get();
        // --- FIN MODIFICACIÓN BENEFICIOS ---
        
        // --- CORRECCIÓN: SOLO DESCUENTOS ACTIVOS ---
        $discounts = Discount::where('is_active', true)->get();
        
        $operators = ['Movistar', 'Vodafone', 'Grupo+Orange', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 0.00);
        $portabilityExceptions = config('commissions.portability_group_exceptions', []);
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();
        
        // Esta consulta ya coge "IP Fija" y "Fibra Oro" (porque ambas son 'internet_feature')
        $fiberFeatures = Addon::where('type', 'internet_feature')->get(); 

        $clientsQuery = Client::query();
        if ($user->isManager()) {
            $teamMembersIds = User::where('team_id', $user->team_id)->pluck('id');
            $clientsQuery->whereIn('user_id', $teamMembersIds);
        } elseif ($user->role === 'user') {
            $clientsQuery->where('user_id', $user->id);
        }
        $clients = $clientsQuery->select('id', 'name', 'cif_nif') 
                                    ->orderBy('name')
                                    ->get();

        $probabilityOptions = [0, 25, 50, 75, 90, 100]; 

        $newClientId = $request->get('new_client_id');

        return Inertia::render('Offers/Create', [
            'packages' => $packages,
            // --- INICIO MODIFICACIÓN BENEFICIOS ---
            'allAddons' => $allAddons, // <- Pasar todos los addons
            // --- FIN MODIFICACIÓN BENEFICIOS ---
            'discounts' => $discounts,
            'operators' => $operators,
            'portabilityCommission' => $portabilityCommission,
            'portabilityExceptions' => $portabilityExceptions,
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'fiberFeatures' => $fiberFeatures, // <-- Se pasan aquí
            'auth' => ['user' => auth()->user()->load('team')],
            'clients' => $clients, 
            'initialClientId' => $newClientId ? (int)$newClientId : null, 
            'probabilityOptions' => $probabilityOptions, 
        ]);
    }
   public function store(Request $request)
    {
        // --- INICIO: VALIDACIÓN ACTUALIZADA ---
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'package_id' => 'required|exists:packages,id',
            'summary' => 'required|array',
            'lines' => 'present|array',
            'internet_addon_id' => 'nullable|exists:addons,id',
            'additional_internet_lines' => 'present|array',
            'additional_internet_lines.*.addon_id' => [ 
                'required', 
                Rule::exists('addons', 'id')->where(function ($query) {
                    $query->where('type', 'internet_additional'); 
                }),
            ],
            'additional_internet_lines.*.has_ip_fija' => 'required|boolean',
            'additional_internet_lines.*.has_fibra_oro' => 'nullable|boolean', // <-- AÑADIDO
            'additional_internet_lines.*.selected_centralita_id' => 'nullable|exists:addons,id', 
            'centralita' => 'present|array',
            'tv_addons' => 'nullable|array',
            'tv_addons.*' => 'exists:addons,id',

            // --- INICIO: AÑADIR VALIDACIÓN ---
            'digital_addons' => 'nullable|array',
            'digital_addons.*' => 'exists:addons,id',
            // --- FIN: AÑADIR VALIDACIÓN ---

            'is_ip_fija_selected' => 'nullable|boolean',
            'is_fibra_oro_selected' => 'nullable|boolean', // <-- AÑADIDO
            'probability' => 'nullable|integer|in:0,25,50,75,90,100',
            'signing_date' => 'nullable|date',
            'processing_date' => 'nullable|date',

            // --- INICIO MODIFICACIÓN BENEFICIOS ---
            'applied_benefit_ids' => 'nullable|array', // Valida que sea un array
            'applied_benefit_ids.*' => 'exists:benefits,id', // Valida que cada ID exista
            // --- FIN MODIFICACIÓN BENEFICIOS ---
        ]);
        // --- FIN: VALIDACIÓN ACTUALIZADA ---

        try {
            DB::transaction(function () use ($validated, $request) {
                
                $package = Package::find($validated['package_id']);
                
                $offer = Offer::create([
                    'package_id' => $validated['package_id'],
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'user_id' => $request->user()->id,
                    'probability' => $validated['probability'] ?? null,
                    'signing_date' => $validated['signing_date'] ?? null,
                    'processing_date' => $validated['processing_date'] ?? null,
                    'package_name' => $package->name ?? 'Paquete no encontrado',
                    'package_price' => $package->base_price ?? 0, // Usamos base_price
                    'package_commission' => $package->commission_amount ?? 0, 
                    'status' => 'borrador', 
                ]);

                // --- INICIO MODIFICACIÓN BENEFICIOS ---
                // Sincroniza los beneficios seleccionados (ej: [1, 5, 7])
                $appliedBenefitIds = $validated['applied_benefit_ids'] ?? [];
                $offer->benefits()->sync($appliedBenefitIds);
                // --- FIN MODIFICACIÓN BENEFICIOS ---

                foreach ($validated['lines'] as $lineData) {
                    $o2oDiscount = null;
                    if (!empty($lineData['o2o_discount_id'])) {
                        $o2oDiscount = O2oDiscount::find($lineData['o2o_discount_id']);
                    }
                    $terminalPivot = null;
                    $terminalName = null;
                    if (!empty($lineData['terminal_pivot_id'])) {
                        $terminalPivot = DB::table('package_terminal')
                            ->join('terminals', 'package_terminal.terminal_id', '=', 'terminals.id')
                            ->where('package_terminal.id', $lineData['terminal_pivot_id'])
                            ->select('terminals.brand', 'terminals.model')
                            ->first();
                        
                        if ($terminalPivot) {
                            $terminalName = $terminalPivot->brand . ' ' . $terminalPivot->model;
                        }
                    }
                    $offer->lines()->create([
                        'is_extra' => $lineData['is_extra'],
                        'is_portability' => $lineData['is_portability'],
                        'phone_number' => $lineData['phone_number'],
                        'source_operator' => $lineData['source_operator'],
                        'has_vap' => $lineData['has_vap'],
                        'o2o_discount_id' => $lineData['o2o_discount_id'],
                        'package_terminal_id' => $lineData['terminal_pivot_id'] ?? null,
                        'initial_cost' => $lineData['initial_cost'],
                        'monthly_cost' => $lineData['monthly_cost'],
                        'o2o_discount_name' => $o2oDiscount->name ?? null,
                        'o2o_discount_amount' => $o2oDiscount->discount_amount ?? null, 
                        'terminal_name' => $terminalName,
                        // --- INICIO: GUARDAR DESCUENTOS (Añadido desde tu Create.vue) ---
                        'initial_cost_discount' => $lineData['initial_cost_discount'] ?? 0,
                        'monthly_cost_discount' => $lineData['monthly_cost_discount'] ?? 0,
                        // --- FIN: GUARDAR DESCUENTOS ---
                    ]);
                }
                
                // --- INICIO: LÓGICA DE ADDONS ACTUALIZADA ---
                
                // 1. IP Fija Principal
                if (!empty($validated['is_ip_fija_selected'])) {
                    // Usamos where() para ser más específicos
                    $ipFijaAddon = Addon::where('name', 'IP Fija')->where('type', 'internet_feature')->first();
                    if ($ipFijaAddon) {
                        $offer->addons()->attach($ipFijaAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $ipFijaAddon->name,
                            'addon_price' => $ipFijaAddon->price,
                            'addon_commission' => $ipFijaAddon->commission, 
                        ]);
                    }
                } 
                // 1.b. (AÑADIDO) Fibra Oro Principal
                if (!empty($validated['is_fibra_oro_selected'])) {
                    $fibraOroAddon = Addon::where('name', 'Fibra Oro')->where('type', 'internet_feature')->first();
                    if ($fibraOroAddon) {
                        $offer->addons()->attach($fibraOroAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $fibraOroAddon->name,
                            'addon_price' => $fibraOroAddon->price,
                            'addon_commission' => $fibraOroAddon->commission, 
                        ]);
                    }
                }

                // 2. Internet Principal
                if (!empty($validated['internet_addon_id'])) {
                    $internetAddon = Addon::find($validated['internet_addon_id']);
                    if ($internetAddon) {
                        $offer->addons()->attach($internetAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $internetAddon->name,
                            'addon_price' => $internetAddon->price, // Aquí debería ser el precio del pivote
                            'addon_commission' => $internetAddon->commission, // Aquí debería ser el precio del pivote
                        ]);
                    }
                }
                
                // 3. Internet Adicional (¡ACTUALIZADO!)
                if (!empty($validated['additional_internet_lines'])) {
                    foreach ($validated['additional_internet_lines'] as $internetLine) {
                        if (!empty($internetLine['addon_id'])) {
                            $additionalAddon = Addon::find($internetLine['addon_id']); 
                            if ($additionalAddon) {
                                $offer->addons()->attach($additionalAddon->id, [
                                    'quantity' => 1,
                                    'has_ip_fija' => $internetLine['has_ip_fija'],
                                    'has_fibra_oro' => $internetLine['has_fibra_oro'] ?? false, // <-- AÑADIDO
                                    'selected_centralita_id' => $internetLine['selected_centralita_id'],
                                    // Snapshot
                                    'addon_name' => $additionalAddon->name,
                                    'addon_price' => $additionalAddon->price,
                                    'addon_commission' => $additionalAddon->commission,
                                ]);
                            }
                        }
                    }
                }
                
                // 4. Centralita (sin cambios)
                $centralitaData = $validated['centralita'];
                if (!empty($centralitaData['id'])) {
                    $centralitaAddon = Addon::find($centralitaData['id']);
                    if ($centralitaAddon) {
                        $offer->addons()->attach($centralitaAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $centralitaAddon->name,
                            'addon_price' => $centralitaAddon->price, // Aquí debería ser el precio del pivote
                            'addon_commission' => $centralitaAddon->commission, // Aquí debería ser el precio del pivote
                        ]);
                    }
                }
                if (!empty($centralitaData['operadora_automatica_selected']) && !empty($centralitaData['operadora_automatica_id'])) {
                    $operadoraAddon = Addon::find($centralitaData['operadora_automatica_id']);
                     if ($operadoraAddon) {
                        $offer->addons()->attach($operadoraAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $operadoraAddon->name,
                            'addon_price' => $operadoraAddon->price, // Aquí debería ser el precio del pivote
                            'addon_commission' => $operadoraAddon->commission, // Aquí debería ser el precio del pivote
                        ]);
                    }
                }
                
                // 5. Extensiones (sin cambios)
                $extensionSyncData = [];
                if (!empty($centralitaData['extensions'])) {
                    foreach ($centralitaData['extensions'] as $ext) {
                        if (!empty($ext['addon_id']) && !empty($ext['quantity']) && $ext['quantity'] > 0) {
                            $extensionAddon = Addon::find($ext['addon_id']); 
                            if ($extensionAddon) {
                                $id = $extensionAddon->id;
                                if (!isset($extensionSyncData[$id])) {
                                    $extensionSyncData[$id] = [
                                        'quantity' => 0,
                                        'addon_name' => $extensionAddon->name,
                                        'addon_price' => $extensionAddon->price,
                                        'addon_commission' => $extensionAddon->commission,
                                    ];
                                }
                                $extensionSyncData[$id]['quantity'] += $ext['quantity'];
                            }
                        }
                    }
                }
                foreach ($extensionSyncData as $id => $pivot) {
                    $offer->addons()->attach($id, $pivot);
                }

                // 6. TV Addons (sin cambios)
                if (!empty($validated['tv_addons'])) {
                    foreach ($validated['tv_addons'] as $tvAddonId) {
                        $tvAddon = Addon::find($tvAddonId);
                        if ($tvAddon) {
                             $offer->addons()->attach($tvAddon->id, [
                                'quantity' => 1,
                                'addon_name' => $tvAddon->name,
                                'addon_price' => $tvAddon->price, // Aquí debería ser el precio del pivote
                                'addon_commission' => $tvAddon->commission, // Aquí debería ser el precio del pivote
                            ]);
                        }
                    }
                }

                // --- INICIO: AÑADIR LÓGICA DE GUARDADO ---
                // 7. Soluciones Digitales (Addons de tipo 'service')
                if (!empty($validated['digital_addons'])) {
                    foreach ($validated['digital_addons'] as $digitalAddonId) {
                        $digitalAddon = Addon::find($digitalAddonId);
                        if ($digitalAddon) {
                             $offer->addons()->attach($digitalAddon->id, [
                                'quantity' => 1,
                                'addon_name' => $digitalAddon->name,
                                'addon_price' => $digitalAddon->price,
                                'addon_commission' => $digitalAddon->commission,
                            ]);
                        }
                    }
                }
                // --- FIN: AÑADIR LÓGICA DE GUARDADO ---
                
                // --- FIN LÓGICA DE ADDONS ---
            });

            return redirect()->route('offers.index')->with('success', 'Oferta guardada correctamente.');

        } catch (\Exception $e) {
             \Log::error('Error storing offer: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()); 
             return back()->withInput()->with('error', 'Error al guardar la oferta. Revisa los datos e inténtalo de nuevo.');
        }
    }
 public function edit(Offer $offer)
    {
        if ($offer->status === 'finalizada' && Auth::user()->role !== 'admin') { 
            return redirect()->route('offers.show', $offer)->with('warning', 'Esta oferta está finalizada y no se puede editar.');
        }

        // --- INICIO MODIFICACIÓN BENEFICIOS ---
        // 1. Cargar todos los addons (incluyendo productos-beneficio)
        $allAddons = Addon::all(); 

        // 2. Cargar paquetes con sus reglas de beneficio y el addon al que apuntan
        $packages = Package::with([
            'addons',
            'o2oDiscounts',
            'terminals' => fn($query) => $query->select('terminals.*', 'package_terminal.id as pivot_id', 'package_terminal.duration_months', 'package_terminal.initial_cost', 'package_terminal.monthly_cost'),
            'benefits.addon' // Carga la regla Y el producto
        ])->get();
        // --- FIN MODIFICACIÓN BENEFICIOS ---
        
        // --- CORRECCIÓN: SOLO DESCUENTOS ACTIVOS ---
        $discounts = Discount::where('is_active', true)->get();
        
        $operators = ['Movistar', 'Vodafone', 'Grupo+Orange', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 0.00);
        $portabilityExceptions = config('commissions.portability_group_exceptions', []);
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();
        
        // Esta consulta ya coge "IP Fija" y "Fibra Oro"
        $fiberFeatures = Addon::where('type', 'internet_feature')->get(); 

        $user = Auth::user();
        $clientsQuery = Client::query();
        if ($user->isManager()) {
            $teamMembersIds = User::where('team_id', $user->team_id)->pluck('id');
            $clientsQuery->whereIn('user_id', $teamMembersIds);
        } elseif ($user->role === 'user') {
            $clientsQuery->where('user_id', $user->id);
        }
        $clients = $clientsQuery->select('id', 'name', 'cif_nif')->orderBy('name')->get();

        $probabilityOptions = [0, 25, 50, 75, 90, 100];

        // --- INICIO: CARGAR PIVOTES (ACTUALIZADO CON BENEFICIOS) ---
        $offer->load([
            'lines', 
            'client',
            'benefits', // <-- ¡AÑADIDO! Carga los beneficios ya seleccionados
            'addons' => function ($query) {
                $query->withPivot(
                    'quantity', 'has_ip_fija', 'selected_centralita_id',
                    'addon_name', 'addon_price', 'addon_commission',
                    'has_fibra_oro' // <-- AÑADIDO
                );
            }
        ]);
        // --- FIN: CARGAR PIVOTES ---

        $offer->lines->each(function ($line) {
            if ($line->package_terminal_id) {
                // --- INICIO CORRECCIÓN PIVOTE TERMINAL ---
                // Cargar el pivote con los descuentos correctos
                $pivotData = DB::table('package_terminal')
                    ->where('id', $line->package_terminal_id)
                    ->first();
                // --- FIN CORRECCIÓN PIVOTE TERMINAL ---
                
                if ($pivotData) {
                    $pivotData->terminal = Terminal::find($pivotData->terminal_id);
                    $line->terminal_pivot = $pivotData;
                } else {
                     $line->terminal_pivot = null; 
                }
            } else {
                $line->terminal_pivot = null;
            }
        });

        // --- INICIO: PREPARAR DATOS VUE (ACTUALIZADO) ---
        
        // 1. IP Fija Principal
        $mainIpFijaSelected = $offer->addons()->where('name', 'IP Fija')->where('type', 'internet_feature')->exists();
        // 1.b. (AÑADIDO) Fibra Oro Principal
        $mainFibraOroSelected = $offer->addons()->where('name', 'Fibra Oro')->where('type', 'internet_feature')->exists();

        // 2. Líneas Adicionales
        $additionalInternetLinesData = $offer->addons()
            ->where('type', 'internet_additional')
            ->get()
            ->map(function ($addon, $index) {
                return [
                    'id' => $addon->id + microtime(true) + $index, 
                    'addon_id' => $addon->id,
                    'has_ip_fija' => (bool) $addon->pivot->has_ip_fija, 
                    'has_fibra_oro' => (bool) $addon->pivot->has_fibra_oro, // <-- AÑADIDO
                    'selected_centralita_id' => $addon->pivot->selected_centralita_id, 
                ];
            })->toArray();
            
        // --- INICIO: AÑADIR CARGA DE SOLUCIONES DIGITALES ---
        // 3. Soluciones Digitales
        $initialSelectedDigitalAddonIds = $offer->addons()
            ->whereIn('type', ['service', 'software']) // Usamos los tipos de tu AddonSeeder
            ->pluck('addons.id') // Pluck 'addons.id' para evitar ambigüedad
            ->toArray();
        // --- FIN: AÑADIR CARGA ---
            
        // ***** INICIO DE LA CORRECCIÓN *****
        // 4. Beneficios (¡ESTO FALTABA!)
        // Ya hemos cargado la relación '$offer->benefits' arriba con load()
        $initialBenefitIds = $offer->benefits->pluck('id')->toArray();
        // ***** FIN DE LA CORRECCIÓN *****
            
        // --- FIN: PREPARAR DATOS VUE ---


        return Inertia::render('Offers/Edit', [
            'offer' => $offer,
            'packages' => $packages,
            // --- INICIO MODIFICACIÓN BENEFICIOS ---
            'allAddons' => $allAddons, // <- Pasar todos los addons
            // --- FIN MODIFICACIÓN BENEFICIOS ---
            'discounts' => $discounts,
            'operators' => $operators,
            'portabilityCommission' => $portabilityCommission,
            'portabilityExceptions' => $portabilityExceptions,
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'fiberFeatures' => $fiberFeatures,
            'auth' => ['user' => auth()->user()->load('team')],
            'clients' => $clients,
            'probabilityOptions' => $probabilityOptions,
            'initialAdditionalInternetLines' => $additionalInternetLinesData, 
            'initialMainIpFijaSelected' => $mainIpFijaSelected,
            'initialMainFibraOroSelected' => $mainFibraOroSelected, // <-- AÑADIDO
            
            // --- INICIO: AÑADIR PROP ---
            'initialSelectedDigitalAddonIds' => $initialSelectedDigitalAddonIds,
            // --- FIN: AÑADIR PROP ---
            
            // ***** INICIO DE LA CORRECCIÓN (Pasar la prop) *****
            'initialSelectedBenefitIds' => $initialBenefitIds, // <-- ¡NUEVA PROP AÑADIDA!
            // ***** FIN DE LA CORRECCIÓN *****
        ]);
    }
   public function update(Request $request, Offer $offer)
    {
         if ($offer->status === 'finalizada' && Auth::user()->role !== 'admin') {
             return back()->with('error', 'Esta oferta está finalizada y no se puede editar.');
         }

         $user = Auth::user();
         $clientId = $request->input('client_id');
         $canAccessClient = false;
         
         if ($user->role === 'admin') {
             $canAccessClient = true;
         } elseif ($user->isManager()) {
             $clientExists = Client::where('id', $clientId)
                                     ->whereIn('user_id', User::where('team_id', $user->team_id)->pluck('id'))
                                     ->exists();
             $canAccessClient = $clientExists;
         } else { 
             $clientExists = Client::where('id', $clientId)->where('user_id', $user->id)->exists();
             $canAccessClient = $clientExists;
         }

         if (!$canAccessClient) {
             return back()->with('error', 'No tienes permiso para asignar esta oferta a ese cliente.');
         }

         // --- INICIO: VALIDACIÓN ACTUALIZADA ---
         $validated = $request->validate([
             'client_id' => 'required|exists:clients,id',
             'package_id' => 'required|exists:packages,id',
             'summary' => 'required|array',
             'lines' => 'present|array',
             'internet_addon_id' => 'nullable|exists:addons,id',
             'additional_internet_lines' => 'present|array',
             'additional_internet_lines.*.addon_id' => [
                 'required',
                 Rule::exists('addons', 'id')->where(function ($query) {
                     $query->where('type', 'internet_additional');
                 }),
             ],
             'additional_internet_lines.*.has_ip_fija' => 'required|boolean',
             'additional_internet_lines.*.has_fibra_oro' => 'nullable|boolean', // <-- AÑADIDO
             'additional_internet_lines.*.selected_centralita_id' => 'nullable|exists:addons,id', 
             'centralita' => 'present|array',
             'tv_addons' => 'nullable|array',
             'tv_addons.*' => 'exists:addons,id',
             
             // --- INICIO: AÑADIR VALIDACIÓN ---
             'digital_addons' => 'nullable|array',
             'digital_addons.*' => 'exists:addons,id',
             // --- FIN: AÑADIR VALIDACIÓN ---
             
             'is_ip_fija_selected' => 'nullable|boolean',
             'is_fibra_oro_selected' => 'nullable|boolean', // <-- AÑADIDO
             'probability' => 'nullable|integer|in:0,25,50,75,90,100',
             'signing_date' => 'nullable|date',
             'processing_date' => 'nullable|date',

            // --- INICIO MODIFICACIÓN BENEFICIOS ---
            'applied_benefit_ids' => 'nullable|array',
            'applied_benefit_ids.*' => 'exists:benefits,id',
            // --- FIN MODIFICACIÓN BENEFICIOS ---
         ]);
        // --- FIN: VALIDACIÓN ACTUALIZADA ---


        try {
            DB::transaction(function () use ($validated, $offer) {

                $package = Package::find($validated['package_id']);

                $offer->update([
                    'package_id' => $validated['package_id'], 
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'probability' => $validated['probability'] ?? null,
                    'signing_date' => $validated['signing_date'] ?? null,
                    'processing_date' => $validated['processing_date'] ?? null,
                    
                    'package_name' => $package->name ?? 'Paquete no encontrado',
                    'package_price' => $package->base_price ?? 0, // Usamos base_price
                    'package_commission' => $package->commission_amount ?? 0,
                ]);

                // --- INICIO MODIFICACIÓN BENEFICIOS ---
                // Sincroniza los beneficios (borra los viejos, añade los nuevos)
                $appliedBenefitIds = $validated['applied_benefit_ids'] ?? [];
                $offer->benefits()->sync($appliedBenefitIds);
                // --- FIN MODIFICACIÓN BENEFICIOS ---

                $offer->lines()->delete();
                foreach ($validated['lines'] as $lineData) {
                    
                    $o2oDiscount = null;
                    if (!empty($lineData['o2o_discount_id'])) {
                        $o2oDiscount = O2oDiscount::find($lineData['o2o_discount_id']);
                    }

                    $terminalPivot = null;
                    $terminalName = null;
                    if (!empty($lineData['terminal_pivot_id'])) {
                        $terminalPivot = DB::table('package_terminal')
                            ->join('terminals', 'package_terminal.terminal_id', '=', 'terminals.id')
                            ->where('package_terminal.id', $lineData['terminal_pivot_id'])
                            ->select('terminals.brand', 'terminals.model')
                            ->first();
                        
                        if ($terminalPivot) {
                            $terminalName = $terminalPivot->brand . ' ' . $terminalPivot->model;
                        }
                    }

                    $offer->lines()->create([
                        'is_extra' => $lineData['is_extra'],
                        'is_portability' => $lineData['is_portability'],
                        'phone_number' => $lineData['phone_number'],
                        'source_operator' => $lineData['source_operator'],
                        'has_vap' => $lineData['has_vap'],
                        'o2o_discount_id' => $lineData['o2o_discount_id'],
                        'package_terminal_id' => $lineData['terminal_pivot_id'] ?? null,
                        'initial_cost' => $lineData['initial_cost'],
                        'monthly_cost' => $lineData['monthly_cost'],

                        'o2o_discount_name' => $o2oDiscount->name ?? null,
                        'o2o_discount_amount' => $o2oDiscount->discount_amount ?? null,
                        'terminal_name' => $terminalName,
                        // --- INICIO: GUARDAR DESCUENTOS (Añadido desde tu Create.vue) ---
                        'initial_cost_discount' => $lineData['initial_cost_discount'] ?? 0,
                        'monthly_cost_discount' => $lineData['monthly_cost_discount'] ?? 0,
                        // --- FIN: GUARDAR DESCUENTOS ---
                    ]);
                }
    // --- INICIO: LÓGICA DE ADDONS ACTUALIZADA ---
                $offer->addons()->detach();

                // 1. IP Fija Principal
                if (!empty($validated['is_ip_fija_selected'])) {
                    $ipFijaAddon = Addon::where('name', 'IP Fija')->where('type', 'internet_feature')->first();
                    if ($ipFijaAddon) {
                        $offer->addons()->attach($ipFijaAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $ipFijaAddon->name,
                            'addon_price' => $ipFijaAddon->price,
                            'addon_commission' => $ipFijaAddon->commission,
                        ]);
                    }
                }

                // 1.b. (AÑADIDO) Fibra Oro Principal
                if (!empty($validated['is_fibra_oro_selected'])) {
                    $fibraOroAddon = Addon::where('name', 'Fibra Oro')->where('type', 'internet_feature')->first();
                    if ($fibraOroAddon) {
                        $offer->addons()->attach($fibraOroAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $fibraOroAddon->name,
                            'addon_price' => $fibraOroAddon->price,
                            'addon_commission' => $fibraOroAddon->commission,
                        ]);
                    }
                }

                // 2. Internet Principal
                if (!empty($validated['internet_addon_id'])) {
                    $internetAddon = Addon::find($validated['internet_addon_id']);
                    if ($internetAddon) {
                        $offer->addons()->attach($internetAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $internetAddon->name,
                            'addon_price' => $internetAddon->price, // Pivote
                            'addon_commission' => $internetAddon->commission, // Pivote
                        ]);
                    }
                }
                
                // 3. Internet Adicional (¡ACTUALIZADO!)
                if (!empty($validated['additional_internet_lines'])) {
                    foreach ($validated['additional_internet_lines'] as $internetLine) {
                        if (!empty($internetLine['addon_id'])) {
                            $additionalAddon = Addon::find($internetLine['addon_id']);
                            if ($additionalAddon) {
                                $offer->addons()->attach($additionalAddon->id, [
                                    'quantity' => 1,
                                    'has_ip_fija' => $internetLine['has_ip_fija'],
                                    'has_fibra_oro' => $internetLine['has_fibra_oro'] ?? false, // <-- AÑADIDO
                                    'selected_centralita_id' => $internetLine['selected_centralita_id'],
                                    'addon_name' => $additionalAddon->name,
                                    'addon_price' => $additionalAddon->price,
                                    'addon_commission' => $additionalAddon->commission,
                                ]);
                            }
                        }
                    }
                }
                
                // 4. Centralita (sin cambios)
                $centralitaData = $validated['centralita'];
                if (!empty($centralitaData['id'])) {
                    $centralitaAddon = Addon::find($centralitaData['id']);
                    if ($centralitaAddon) {
                        $offer->addons()->attach($centralitaAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $centralitaAddon->name,
                            'addon_price' => $centralitaAddon->price, // Pivote
                            'addon_commission' => $centralitaAddon->commission, // Pivote
                        ]);
                    }
                }
                if (!empty($centralitaData['operadora_automatica_selected']) && !empty($centralitaData['operadora_automatica_id'])) {
                    $operadoraAddon = Addon::find($centralitaData['operadora_automatica_id']);
                     if ($operadoraAddon) {
                        $offer->addons()->attach($operadoraAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $operadoraAddon->name,
                            'addon_price' => $operadoraAddon->price, // Pivote
                            'addon_commission' => $operadoraAddon->commission, // Pivote
                        ]);
                    }
                }
                
                // 5. Extensiones (sin cambios)
                $extensionSyncData = [];
                if (!empty($centralitaData['extensions'])) {
                    foreach ($centralitaData['extensions'] as $ext) {
                        if (!empty($ext['addon_id']) && !empty($ext['quantity']) && $ext['quantity'] > 0) {
                            $extensionAddon = Addon::find($ext['addon_id']);
                            if ($extensionAddon) {
                                $id = $extensionAddon->id;
                                if (!isset($extensionSyncData[$id])) {
                                    $extensionSyncData[$id] = [
                                        'quantity' => 0,
                                        'addon_name' => $extensionAddon->name,
                                        'addon_price' => $extensionAddon->price,
                                        'addon_commission' => $extensionAddon->commission,
                                    ];
                                }
                                $extensionSyncData[$id]['quantity'] += $ext['quantity'];
                            }
                        }
                    }
                }
                foreach ($extensionSyncData as $id => $pivot) {
                    $offer->addons()->attach($id, $pivot);
                }

                // 6. TV Addons (sin cambios)
                if (!empty($validated['tv_addons'])) {
                    foreach ($validated['tv_addons'] as $tvAddonId) {
                        $tvAddon = Addon::find($tvAddonId);
                        if ($tvAddon) {
                             $offer->addons()->attach($tvAddon->id, [
                                'quantity' => 1,
                                'addon_name' => $tvAddon->name,
                                'addon_price' => $tvAddon->price, // Pivote
                                'addon_commission' => $tvAddon->commission, // Pivote
                            ]);
                        }
                    }
                }
                
                // --- INICIO: AÑADIR LÓGICA DE GUARDADO ---
                // 7. Soluciones Digitales (Addons de tipo 'service')
                if (!empty($validated['digital_addons'])) {
                    foreach ($validated['digital_addons'] as $digitalAddonId) {
                        $digitalAddon = Addon::find($digitalAddonId);
                        if ($digitalAddon) {
                             $offer->addons()->attach($digitalAddon->id, [
                                'quantity' => 1,
                                'addon_name' => $digitalAddon->name,
                                'addon_price' => $digitalAddon->price,
                                'addon_commission' => $digitalAddon->commission,
                            ]);
                        }
                    }
                }
                // --- FIN: AÑADIR LÓGICA DE GUARDADO ---
                
                // --- FIN LÓGICA DE ADDONS ---

            });

            return redirect()->route('offers.index')->with('success', '¡Oferta actualizada correctamente!');

        } catch (\Exception $e) {
            \Log::error('Error updating offer '.$offer->id.': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return back()->withInput()->with('error', 'No se pudo actualizar la oferta. Revisa los datos.');
        }
    }
   public function show(Offer $offer)
    {
        // --- INICIO: CARGAR PIVOTES (ACTUALIZADO CON BENEFICIOS) ---
        $offer->load([
            'package.addons', 
            'user.team', 
            'lines', 
            'client',
            'benefits.addon', // <-- ¡AÑADIDO!
            'addons' => function ($query) {
                $query->withPivot(
                    'quantity', 'has_ip_fija', 'selected_centralita_id',
                    'addon_name', 'addon_price', 'addon_commission',
                    'has_fibra_oro' // <-- AÑADIDO
                );
            }
        ]);
        // --- FIN: CARGAR PIVOTES ---

        $offer->lines->each(function ($line) {
            if ($line->terminal_name) {
                 $line->terminal_details = (object) [
                    'brand' => Str::before($line->terminal_name, ' ') ?? 'Terminal',
                    'model' => Str::after($line->terminal_name, ' ') ?? $line->terminal_name,
                    'duration_months' => 'N/A' 
                 ];
            } else {
                $line->terminal_details = null;
            }
        });

        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();
        return Inertia::render('Offers/Show', [
            'offer' => $offer,
            'centralitaExtensions' => $centralitaExtensions,
        ]);
    }


    public function generatePDF(Offer $offer)
    {
        set_time_limit(300);

        // --- INICIO: CARGAR PIVOTES (ACTUALIZADO CON BENEFICIOS) ---
        $offer->load([
            'package.addons', 
            'user', 
            'lines', 
            'client',
            'benefits.addon', // <-- ¡AÑADIDO!
            'addons' => function ($query) {
                $query->withPivot(
                    'quantity', 'has_ip_fija', 'selected_centralita_id',
                    'addon_name', 'addon_price', 'addon_commission',
                    'has_fibra_oro' // <-- AÑADIDO
                );
            }
        ]);
        // --- FIN: CARGAR PIVOTES ---

        $offer->lines->each(function ($line) {
             if ($line->terminal_name) {
                 $line->terminal_details = (object) [
                    'brand' => Str::before($line->terminal_name, ' ') ?? 'Terminal',
                    'model' => Str::after($line->terminal_name, ' ') ?? $line->terminal_name,
                    'duration_months' => 'N/A'
                 ];
            } else {
                $line->terminal_details = null;
            }
        });

        $pdf = PDF::loadView('pdfs.offer_pdf', compact('offer'));

        $clientName = $offer->client ? Str::slug($offer->client->name) : 'sin-cliente';
        $fileName = 'oferta-' . $offer->id . '-' . $clientName . '.pdf';

        return $pdf->download($fileName);
    }

    public function destroy(Offer $offer)
    {
        try {
            DB::transaction(function () use ($offer) {
                 $offer->lines()->delete();
                 $offer->addons()->detach();
                 $offer->benefits()->detach(); // <-- INCLUIDO PARA LIMPIEZA
                 $offer->delete();
            });
            return redirect()->route('offers.index')->with('success', 'Oferta eliminada correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error deleting offer '.$offer->id.': '.$e->getMessage());
             return redirect()->route('offers.index')->with('error', 'No se pudo eliminar la oferta. Puede tener elementos asociados.');
        }
    }

    public function lock(Request $request, Offer $offer)
    {
        try {
            $offer->update(['status' => 'finalizada']);
            return back()->with('success', 'Oferta finalizada y bloqueada para edición.');
        } catch (\Exception $e) {
            \Log::error('Error locking offer '.$offer->id.': '.$e->getMessage());
            return back()->with('error', 'No se pudo bloquear la oferta.');
        }
    }
    
    public function exportFunnel(Request $request)
    {
        $user = Auth::user();

        $allowedRoles = ['admin', 'jefe de ventas', 'team_lead'];
        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'No tienes permiso para realizar esta exportación.');
        }
        
        $query = Offer::with(['user.team','client']) 
                            ->withCount('lines')
                            ->latest();

        if ($user->role === 'jefe de ventas' || $user->role === 'team_lead') {
             if ($user->team_id) {
                 $teamMemberIds = User::where('team_id', $user->team_id)->pluck('id');
                 $teamMemberIds->push($user->id);
                 $query->whereIn('user_id', $teamMemberIds->unique());
             } else {
                  $query->where('user_id', $user->id);
             }
        }

        $offersToExport = $query->get();
        
        $filename = "funnel_ofertas_" . date('Ymd_His') . ".csv";
        $headers = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function() use ($offersToExport) {
            $file = fopen('php://output', 'w');
             fwrite($file, "\xEF\xBB\xBF"); // BOM

            fputcsv($file, [
                'ID Oferta', 'Cliente', 'CIF/NIF', 'Vendedor', 'Equipo Vendedor',
                'Paquete (Snapshot)', 'Precio Final (€)', 'Probabilidad (%)', 'Fecha Firma',
                'Fecha Tramitación', 'Fecha Creación', 'Num. Líneas Móviles', 'Estado',
            ], ';');

            foreach ($offersToExport as $offer) {
                fputcsv($file, [
                    $offer->id,
                    $offer->client?->name ?? 'N/A',
                    $offer->client?->cif_nif ?? 'N/A',
                    $offer->user?->name ?? 'N/A',
                    $offer->user?->team?->name ?? 'N/A',
                    $offer->package_name ?? 'N/A', 
                    number_format($offer->summary['finalPrice'] ?? 0, 2, '.', ''),
                    $offer->probability ?? '',
                    $offer->signing_date ? $offer->signing_date->format('Y-m-d') : '',
                    $offer->processing_date ? $offer->processing_date->format('Y-m-d') : '',
                    $offer->created_at ? $offer->created_at->format('Y-m-d H:i:s') : '',
                    $offer->lines_count ?? 0,
                    $offer->status ?? 'borrador', 
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Envía la oferta por email a la dirección especificada.
     */
    public function send(Request $request, Offer $offer)
    {
        // 1. Validar que el email recibido es un email válido
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $targetEmail = $validated['email'];

        try {
            // 2. Cargar todas las relaciones necesarias para el PDF y el Email
            $offer->load([
                'package.addons', 
                'user', 
                'lines', 
                'client',
                'benefits.addon',
                'addons' => function ($query) {
                    $query->withPivot(
                        'quantity', 'has_ip_fija', 'selected_centralita_id',
                        'addon_name', 'addon_price', 'addon_commission',
                        'has_fibra_oro'
                    );
                }
            ]);
            
            // 3. Genera el PDF primero
             $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.offer_pdf', compact('offer'));
             $clientName = $offer->client ? \Illuminate\Support\Str::slug($offer->client->name) : 'sin-cliente';
             $fileName = 'oferta-' . $offer->id . '-' . $clientName . '.pdf';

            // 4. Envía el email adjuntando el PDF
            Mail::send([], [], function ($message) use ($offer, $pdf, $fileName, $targetEmail) {
                $message->to($targetEmail)
                        ->subject('Tu propuesta comercial de Orange - Oferta #' . $offer->id)
                        ->attachData($pdf->output(), $fileName, [
                            'mime' => 'application/pdf',
                        ])
                        ->html('<p>Adjuntamos tu propuesta comercial. ¡Gracias por confiar en nosotros!</p>');
            });

            return back()->with('success', '¡Oferta enviada por email a ' . $targetEmail . '!');

        } catch (\Exception $e) {
            \Log::error('Error enviando email oferta '.$offer->id.': '.$e->getMessage());
            return back()->with('error', 'No se pudo enviar el email. Inténtalo de nuevo.');
        }
    }
}