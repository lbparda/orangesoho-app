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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

// --- INICIO: IMPORTACIONES AÑADIDAS ---
// Necesitamos estos modelos para buscar los datos antes de copiarlos
use App\Models\O2oDiscount;
// Nota: Package, Addon, Terminal y User ya estaban importados.
// --- FIN: IMPORTACIONES AÑADIDAS ---

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Usamos isManager() para simplificar
        // Los nuevos campos (probability, signing_date, processing_date, status)
        // se cargarán automáticamente aquí, ya que son parte del modelo Offer.
        $query = Offer::with(['package', 'user.team','client'])->latest(); // package se mantiene para referencia

        if ($user->isManager()) { // Comprueba 'team_lead' y 'jefe de ventas'
            if ($user->team_id) {
                $teamMemberIds = User::where('team_id', $user->team_id)->pluck('id');
                $query->whereIn('user_id', $teamMemberIds);
            } else {
                // Si un manager no tiene equipo, solo ve los suyos
                $query->where('user_id', $user->id);
            }
        } elseif($user->role === 'user') { // Rol 'user' explícito
             $query->where('user_id', $user->id);
        }
        // Admin ve todo (no se aplica filtro)

        return Inertia::render('Offers/Index', [
            'offers' => $query->paginate(10)
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $packages = Package::with(['addons', 'o2oDiscounts', 'terminals'])->get();
        $discounts = Discount::all();
        $operators = ['Movistar', 'Vodafone', 'Grupo+Orange', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 0.00);
        $portabilityExceptions = config('commissions.portability_group_exceptions', []);
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();
        $fiberFeatures = Addon::where('type', 'internet_feature')->get(); // IP Fija

        // --- LÓGICA DE FILTRADO DE CLIENTES ---
        $clientsQuery = Client::query();
        if ($user->isManager()) {
            $teamMembersIds = User::where('team_id', $user->team_id)->pluck('id');
            $clientsQuery->whereIn('user_id', $teamMembersIds);
        } elseif ($user->role === 'user') {
            $clientsQuery->where('user_id', $user->id);
        }
        // Admin ve todos
        $clients = $clientsQuery->select('id', 'name', 'cif_nif') // Selecciona campos útiles
                                 ->orderBy('name')
                                 ->get();
        // --- FIN LÓGICA DE FILTRADO ---

        $probabilityOptions = [0, 25, 50, 75, 90, 100];

        $newClientId = $request->get('new_client_id');

        return Inertia::render('Offers/Create', [
            'packages' => $packages,
            'discounts' => $discounts,
            'operators' => $operators,
            'portabilityCommission' => $portabilityCommission,
            'portabilityExceptions' => $portabilityExceptions,
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'fiberFeatures' => $fiberFeatures,
            'auth' => ['user' => auth()->user()->load('team')],
            'clients' => $clients,
            'initialClientId' => $newClientId ? (int)$newClientId : null,
            'probabilityOptions' => $probabilityOptions,
        ]);
    }

    public function store(Request $request)
    {
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
            'additional_internet_lines.*.selected_centralita_id' => 'nullable|exists:addons,id',
            'centralita' => 'present|array',
            'tv_addons' => 'nullable|array',
            'tv_addons.*' => 'exists:addons,id',
            'is_ip_fija_selected' => 'nullable|boolean',
            'probability' => 'nullable|integer|in:0,25,50,75,90,100',
            'signing_date' => 'nullable|date',
            'processing_date' => 'nullable|date',
        ]);


        try {
            DB::transaction(function () use ($validated, $request) {
                
                // --- INICIO LÓGICA SNAPSHOT (Paso 1: Paquete) ---
                $package = Package::find($validated['package_id']);
                // --- FIN LÓGICA SNAPSHOT ---

                $offer = Offer::create([
                    'package_id' => $validated['package_id'], // Guardamos la referencia
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'user_id' => $request->user()->id,
                    'probability' => $validated['probability'] ?? null,
                    'signing_date' => $validated['signing_date'] ?? null,
                    'processing_date' => $validated['processing_date'] ?? null,

                    // --- INICIO CAMPOS SNAPSHOT (Paquete) ---
                    'package_name' => $package->name ?? 'Paquete no encontrado',
                    'package_price' => $package->price ?? 0,
                    'package_commission' => $package->commission_amount ?? 0, // Ajusta 'commission_amount' al nombre real de tu columna en 'packages'
                    'status' => 'borrador', // Estado inicial
                    // --- FIN CAMPOS SNAPSHOT ---
                ]);

                foreach ($validated['lines'] as $lineData) {

                    // --- INICIO LÓGICA SNAPSHOT (Paso 2: Líneas) ---
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
                    // --- FIN LÓGICA SNAPSHOT ---

                    $offer->lines()->create([
                        'is_extra' => $lineData['is_extra'],
                        'is_portability' => $lineData['is_portability'],
                        'phone_number' => $lineData['phone_number'],
                        'source_operator' => $lineData['source_operator'],
                        'has_vap' => $lineData['has_vap'],
                        'o2o_discount_id' => $lineData['o2o_discount_id'], // Referencia
                        'package_terminal_id' => $lineData['terminal_pivot_id'] ?? null, // Referencia
                        'initial_cost' => $lineData['initial_cost'], // Coste final calculado
                        'monthly_cost' => $lineData['monthly_cost'], // Coste final calculado

                        // --- INICIO CAMPOS SNAPSHOT (Líneas) ---
                        'o2o_discount_name' => $o2oDiscount->name ?? null,
                        'o2o_discount_amount' => $o2oDiscount->discount_amount ?? null, // Ajusta 'discount_amount' al nombre real
                        'terminal_name' => $terminalName,
                        // --- FIN CAMPOS SNAPSHOT ---
                    ]);
                }

                // --- INICIO LÓGICA SNAPSHOT (Paso 3: Addons) ---
                
                // 1. IP Fija Principal
                if (!empty($validated['is_ip_fija_selected'])) {
                    $ipFijaAddon = Addon::where('type', 'internet_feature')->first();
                    if ($ipFijaAddon) {
                        $offer->addons()->attach($ipFijaAddon->id, [
                            'quantity' => 1,
                            // Snapshot
                            'addon_name' => $ipFijaAddon->name,
                            'addon_price' => $ipFijaAddon->price,
                            'addon_commission' => $ipFijaAddon->commission, // Ajusta 'commission' al nombre real
                        ]);
                    }
                }

                // 2. Internet Principal
                if (!empty($validated['internet_addon_id'])) {
                    $internetAddon = Addon::find($validated['internet_addon_id']);
                    if ($internetAddon) {
                        $offer->addons()->attach($internetAddon->id, [
                            'quantity' => 1,
                            // Snapshot
                            'addon_name' => $internetAddon->name,
                            'addon_price' => $internetAddon->price,
                            'addon_commission' => $internetAddon->commission,
                        ]);
                    }
                }
                
                // 3. Internet Adicional
                if (!empty($validated['additional_internet_lines'])) {
                    foreach ($validated['additional_internet_lines'] as $internetLine) {
                        if (!empty($internetLine['addon_id'])) {
                            $additionalAddon = Addon::find($internetLine['addon_id']); // ¡Buscar el addon!
                            if ($additionalAddon) {
                                $offer->addons()->attach($additionalAddon->id, [
                                    'quantity' => 1,
                                    'has_ip_fija' => $internetLine['has_ip_fija'],
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
                
                // 4. Centralita Principal
                $centralitaData = $validated['centralita'];
                if (!empty($centralitaData['id'])) {
                    $centralitaAddon = Addon::find($centralitaData['id']);
                    if ($centralitaAddon) {
                        $offer->addons()->attach($centralitaAddon->id, [
                            'quantity' => 1,
                            // Snapshot
                            'addon_name' => $centralitaAddon->name,
                            'addon_price' => $centralitaAddon->price,
                            'addon_commission' => $centralitaAddon->commission,
                        ]);
                    }
                }
                if (!empty($centralitaData['operadora_automatica_selected']) && !empty($centralitaData['operadora_automatica_id'])) {
                    $operadoraAddon = Addon::find($centralitaData['operadora_automatica_id']);
                     if ($operadoraAddon) {
                        $offer->addons()->attach($operadoraAddon->id, [
                            'quantity' => 1,
                            // Snapshot
                            'addon_name' => $operadoraAddon->name,
                            'addon_price' => $operadoraAddon->price,
                            'addon_commission' => $operadoraAddon->commission,
                        ]);
                    }
                }
                
                // 5. Extensiones
                $extensionSyncData = [];
                if (!empty($centralitaData['extensions'])) {
                    foreach ($centralitaData['extensions'] as $ext) {
                        if (!empty($ext['addon_id']) && !empty($ext['quantity']) && $ext['quantity'] > 0) {
                            $extensionAddon = Addon::find($ext['addon_id']); // ¡Buscar el addon!
                            if ($extensionAddon) {
                                // Usamos el ID como clave para agrupar
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
                    $offer->addons()->attach($id, $pivot); // Adjuntamos las extensiones agrupadas CON snapshot
                }

                // 6. TV Addons
                if (!empty($validated['tv_addons'])) {
                    foreach ($validated['tv_addons'] as $tvAddonId) {
                        $tvAddon = Addon::find($tvAddonId);
                        if ($tvAddon) {
                             $offer->addons()->attach($tvAddon->id, [
                                'quantity' => 1,
                                // Snapshot
                                'addon_name' => $tvAddon->name,
                                'addon_price' => $tvAddon->price,
                                'addon_commission' => $tvAddon->commission,
                            ]);
                        }
                    }
                }
                // --- FIN LÓGICA SNAPSHOT ---
            });

            return redirect()->route('offers.index')->with('success', 'Oferta guardada correctamente.');

        } catch (\Exception $e) {
             \Log::error('Error storing offer: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
             return back()->withInput()->with('error', 'Error al guardar la oferta. Revisa los datos e inténtalo de nuevo.');
        }
    }

    public function edit(Offer $offer)
    {
        // --- INICIO: LÓGICA DE BLOQUEO ---
        if ($offer->status === 'finalizada' && Auth::user()->role !== 'admin') { // Permitir a admin editar siempre
             return redirect()->route('offers.show', $offer)->with('warning', 'Esta oferta está finalizada y no se puede editar.');
        }
        // --- FIN: LÓGICA DE BLOQUEO ---


        $packages = Package::with([
            'addons',
            'o2oDiscounts',
            'terminals' => fn($query) => $query->select('terminals.*', 'package_terminal.id as pivot_id', 'package_terminal.duration_months', 'package_terminal.initial_cost', 'package_terminal.monthly_cost')
        ])->get();
        $discounts = Discount::all();
        $operators = ['Movistar', 'Vodafone', 'Grupo+Orange', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 0.00);
        $portabilityExceptions = config('commissions.portability_group_exceptions', []);
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();
        $fiberFeatures = Addon::where('type', 'internet_feature')->get(); // IP Fija

        // --- Filtrado de Clientes también en Edit ---
        $user = Auth::user();
        $clientsQuery = Client::query();
        if ($user->isManager()) {
            $teamMembersIds = User::where('team_id', $user->team_id)->pluck('id');
            $clientsQuery->whereIn('user_id', $teamMembersIds);
        } elseif ($user->role === 'user') {
            $clientsQuery->where('user_id', $user->id);
        }
        $clients = $clientsQuery->select('id', 'name', 'cif_nif')->orderBy('name')->get();
        // --- Fin Filtrado ---

        $probabilityOptions = [0, 25, 50, 75, 90, 100];

        // --- INICIO CÓDIGO MODIFICADO: Cargar pivotes (¡CON CAMPOS SNAPSHOT!) ---
        $offer->load(['lines', 'client', 'addons' => function ($query) {
                $query->withPivot(
                    'quantity', 'has_ip_fija', 'selected_centralita_id',
                    'addon_name', 'addon_price', 'addon_commission' // Cargar nuevos campos
                );
            }
        ]);
        // --- FIN CÓDIGO MODIFICADO ---

        // Cargar detalles del terminal para cada línea (como en show y pdf)
        $offer->lines->each(function ($line) {
            if ($line->package_terminal_id) {
                $pivotData = DB::table('package_terminal')->find($line->package_terminal_id);
                if ($pivotData) {
                    $pivotData->terminal = Terminal::find($pivotData->terminal_id);
                    $line->terminal_pivot = $pivotData;
                } else {
                     // El terminal ya no existe, pero los datos snapshot están en la línea
                     $line->terminal_pivot = null; 
                }
            } else {
                $line->terminal_pivot = null;
            }
        });


        // --- INICIO CÓDIGO MODIFICADO: Preparar datos para Vue ---
        // (Esta lógica sigue igual, ya que lee los pivotes que acabamos de cargar)
        
        // 1. IP Fija Principal
        $mainIpFijaSelected = $offer->addons()->where('type', 'internet_feature')->exists();

        // 2. Líneas Adicionales
        $additionalInternetLinesData = $offer->addons()
            ->where('type', 'internet_additional')
            ->get()
            ->map(function ($addon, $index) {
                return [
                    'id' => $addon->id + microtime(true) + $index, // ID único para Vue
                    'addon_id' => $addon->id,
                    'has_ip_fija' => (bool) $addon->pivot->has_ip_fija,
                    'selected_centralita_id' => $addon->pivot->selected_centralita_id,
                ];
            })->toArray();
        // --- FIN CÓDIGO MODIFICADO ---


        return Inertia::render('Offers/Edit', [
            'offer' => $offer,
            'packages' => $packages,
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
        ]);
    }


    public function update(Request $request, Offer $offer)
    {
        // --- INICIO: LÓGICA DE BLOQUEO ---
        if ($offer->status === 'finalizada' && Auth::user()->role !== 'admin') {
             return back()->with('error', 'Esta oferta está finalizada y no se puede editar.');
        }
        // --- FIN: LÓGICA DE BLOQUEO ---

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
         } else { // user role
              $clientExists = Client::where('id', $clientId)->where('user_id', $user->id)->exists();
              $canAccessClient = $clientExists;
         }

         if (!$canAccessClient) {
             return back()->with('error', 'No tienes permiso para asignar esta oferta a ese cliente.');
         }

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
             'additional_internet_lines.*.selected_centralita_id' => 'nullable|exists:addons,id',
             'centralita' => 'present|array',
             'tv_addons' => 'nullable|array',
             'tv_addons.*' => 'exists:addons,id',
             'is_ip_fija_selected' => 'nullable|boolean',
             'probability' => 'nullable|integer|in:0,25,50,75,90,100',
             'signing_date' => 'nullable|date',
             'processing_date' => 'nullable|date',
         ]);


        try {
            DB::transaction(function () use ($validated, $offer) {

                // --- INICIO LÓGICA SNAPSHOT (Paso 1: Paquete) ---
                $package = Package::find($validated['package_id']);
                // --- FIN LÓGICA SNAPSHOT ---

                $offer->update([
                    'package_id' => $validated['package_id'], // Actualizar referencia
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'probability' => $validated['probability'] ?? null,
                    'signing_date' => $validated['signing_date'] ?? null,
                    'processing_date' => $validated['processing_date'] ?? null,
                    
                    // --- INICIO CAMPOS SNAPSHOT (Paquete) ---
                    'package_name' => $package->name ?? 'Paquete no encontrado',
                    'package_price' => $package->price ?? 0,
                    'package_commission' => $package->commission_amount ?? 0, // Ajusta 'commission_amount'
                    // No actualizamos el 'status' aquí
                    // --- FIN CAMPOS SNAPSHOT ---
                ]);

                // Borrar y recrear líneas (Lógica idéntica a store)
                $offer->lines()->delete();
                foreach ($validated['lines'] as $lineData) {
                    
                    // --- INICIO LÓGICA SNAPSHOT (Paso 2: Líneas) ---
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
                    // --- FIN LÓGICA SNAPSHOT ---

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

                        // --- INICIO CAMPOS SNAPSHOT (Líneas) ---
                        'o2o_discount_name' => $o2oDiscount->name ?? null,
                        'o2o_discount_amount' => $o2oDiscount->discount_amount ?? null,
                        'terminal_name' => $terminalName,
                        // --- FIN CAMPOS SNAPSHOT ---
                    ]);
                }

                // --- INICIO LÓGICA SNAPSHOT (Paso 3: Addons - Lógica idéntica a store) ---
                $offer->addons()->detach();

                // 1. IP Fija Principal
                if (!empty($validated['is_ip_fija_selected'])) {
                    $ipFijaAddon = Addon::where('type', 'internet_feature')->first();
                    if ($ipFijaAddon) {
                        $offer->addons()->attach($ipFijaAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $ipFijaAddon->name,
                            'addon_price' => $ipFijaAddon->price,
                            'addon_commission' => $ipFijaAddon->commission,
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
                            'addon_price' => $internetAddon->price,
                            'addon_commission' => $internetAddon->commission,
                        ]);
                    }
                }
                
                // 3. Internet Adicional
                if (!empty($validated['additional_internet_lines'])) {
                    foreach ($validated['additional_internet_lines'] as $internetLine) {
                        if (!empty($internetLine['addon_id'])) {
                            $additionalAddon = Addon::find($internetLine['addon_id']);
                            if ($additionalAddon) {
                                $offer->addons()->attach($additionalAddon->id, [
                                    'quantity' => 1,
                                    'has_ip_fija' => $internetLine['has_ip_fija'],
                                    'selected_centralita_id' => $internetLine['selected_centralita_id'],
                                    'addon_name' => $additionalAddon->name,
                                    'addon_price' => $additionalAddon->price,
                                    'addon_commission' => $additionalAddon->commission,
                                ]);
                            }
                        }
                    }
                }
                
                // 4. Centralita Principal
                $centralitaData = $validated['centralita'];
                if (!empty($centralitaData['id'])) {
                    $centralitaAddon = Addon::find($centralitaData['id']);
                    if ($centralitaAddon) {
                        $offer->addons()->attach($centralitaAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $centralitaAddon->name,
                            'addon_price' => $centralitaAddon->price,
                            'addon_commission' => $centralitaAddon->commission,
                        ]);
                    }
                }
                if (!empty($centralitaData['operadora_automatica_selected']) && !empty($centralitaData['operadora_automatica_id'])) {
                    $operadoraAddon = Addon::find($centralitaData['operadora_automatica_id']);
                     if ($operadoraAddon) {
                        $offer->addons()->attach($operadoraAddon->id, [
                            'quantity' => 1,
                            'addon_name' => $operadoraAddon->name,
                            'addon_price' => $operadoraAddon->price,
                            'addon_commission' => $operadoraAddon->commission,
                        ]);
                    }
                }
                
                // 5. Extensiones
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

                // 6. TV Addons
                if (!empty($validated['tv_addons'])) {
                    foreach ($validated['tv_addons'] as $tvAddonId) {
                        $tvAddon = Addon::find($tvAddonId);
                        if ($tvAddon) {
                             $offer->addons()->attach($tvAddon->id, [
                                'quantity' => 1,
                                'addon_name' => $tvAddon->name,
                                'addon_price' => $tvAddon->price,
                                'addon_commission' => $tvAddon->commission,
                            ]);
                        }
                    }
                }
                // --- FIN LÓGICA SNAPSHOT (UPDATE) ---

            });

            return redirect()->route('offers.index')->with('success', '¡Oferta actualizada correctamente!');

        } catch (\Exception $e) {
            \Log::error('Error updating offer '.$offer->id.': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return back()->withInput()->with('error', 'No se pudo actualizar la oferta. Revisa los datos.');
        }
    }


    public function show(Offer $offer)
    {
        // --- INICIO CÓDIGO MODIFICADO: Cargar pivotes (¡CON CAMPOS SNAPSHOT!) ---
        $offer->load([
            'package.addons',
            'user.team', 
            'lines', // lines ya tiene los campos snapshot
            'client',
            'addons' => function ($query) {
                $query->withPivot(
                    'quantity', 'has_ip_fija', 'selected_centralita_id',
                    'addon_name', 'addon_price', 'addon_commission' // Cargar nuevos campos
                );
            }
        ]);
        // --- FIN CÓDIGO MODIFICADO ---

        // Cargar detalles del terminal (Esta lógica se puede simplificar)
        $offer->lines->each(function ($line) {
            // Ya no necesitamos buscar en la BD, ¡usamos el snapshot!
            if ($line->terminal_name) {
                 // Creamos un objeto 'terminal_details' falso para consistencia
                 $line->terminal_details = (object) [
                    'brand' => Str::before($line->terminal_name, ' ') ?? 'Terminal',
                    'model' => Str::after($line->terminal_name, ' ') ?? $line->terminal_name,
                    'duration_months' => 'N/A' // Este dato no lo guardamos, podríamos añadirlo
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

        // --- INICIO CÓDIGO MODIFICADO: Cargar pivotes (¡CON CAMPOS SNAPSHOT!) ---
        $offer->load([
            'package.addons', 
            'user', 
            'lines', 
            'client',
            'addons' => function ($query) {
                $query->withPivot(
                    'quantity', 'has_ip_fija', 'selected_centralita_id',
                    'addon_name', 'addon_price', 'addon_commission' // Cargar nuevos campos
                );
            }
        ]);
        // --- FIN CÓDIGO MODIFICADO ---

        // Cargar detalles del terminal (Lógica de Show, basada en snapshot)
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
         // --- INICIO: LÓGICA DE BLOQUEO (Opcional) ---
         // if ($offer->status === 'finalizada' && Auth::user()->role !== 'admin') {
         //    return back()->with('error', 'No se puede eliminar una oferta finalizada.');
         // }
         // --- FIN: LÓGICA DE BLOQUEO ---

        try {
            DB::transaction(function () use ($offer) {
                 $offer->lines()->delete();
                 $offer->addons()->detach();
                 $offer->delete();
            });
            return redirect()->route('offers.index')->with('success', 'Oferta eliminada correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error deleting offer '.$offer->id.': '.$e->getMessage());
             return redirect()->route('offers.index')->with('error', 'No se pudo eliminar la oferta. Puede tener elementos asociados.');
        }
    }

    // --- INICIO: NUEVO MÉTODO PARA BLOQUEAR OFERTAS ---
    /**
     * Bloquea una oferta para que no se pueda editar.
     */
    public function lock(Request $request, Offer $offer)
    {
        // Opcional: Añadir autorización
        // if (Auth::user()->cannot('lock', $offer)) {
        //     abort(403);
        // }

        try {
            $offer->update(['status' => 'finalizada']);
            return back()->with('success', 'Oferta finalizada y bloqueada para edición.');
        } catch (\Exception $e) {
            \Log::error('Error locking offer '.$offer->id.': '.$e->getMessage());
            return back()->with('error', 'No se pudo bloquear la oferta.');
        }
    }
    // --- FIN: NUEVO MÉTODO ---


    public function exportFunnel(Request $request)
    {
        $user = Auth::user();

        $allowedRoles = ['admin', 'jefe de ventas', 'team_lead'];
        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'No tienes permiso para realizar esta exportación.');
        }
        
        // La consulta funciona igual, pero ahora usamos el 'package_name' de la oferta
        $query = Offer::with(['user.team','client']) // 'package' ya no es necesario aquí
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
                'Fecha Tramitación', 'Fecha Creación', 'Num. Líneas Móviles', 'Estado', // Añadido Estado
            ], ';');

            foreach ($offersToExport as $offer) {
                fputcsv($file, [
                    $offer->id,
                    $offer->client?->name ?? 'N/A',
                    $offer->client?->cif_nif ?? 'N/A',
                    $offer->user?->name ?? 'N/A',
                    $offer->user?->team?->name ?? 'N/A',
                    $offer->package_name ?? 'N/A', // --- CAMBIO: Usar el campo snapshot ---
                    number_format($offer->summary['finalPrice'] ?? 0, 2, '.', ''),
                    $offer->probability ?? '',
                    $offer->signing_date ? $offer->signing_date->format('Y-m-d') : '',
                    $offer->processing_date ? $offer->processing_date->format('Y-m-d') : '',
                    $offer->created_at ? $offer->created_at->format('Y-m-d H:i:s') : '',
                    $offer->lines_count ?? 0,
                    $offer->status ?? 'borrador', // --- CAMBIO: Añadido estado ---
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

}