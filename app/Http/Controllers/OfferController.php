<?php

namespace App\Http\Controllers; // <-- Asegúrate que esta línea esté correcta

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
use Illuminate\Support\Facades\Auth; // <-- Importante
use Inertia\Inertia;
use Illuminate\Support\Str; // <-- Importante
use Illuminate\Support\Facades\Response; // <-- AÑADE ESTA LÍNEA
use Illuminate\Validation\Rule; // <-- AÑADIDO: Para reglas de validación más complejas

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Usamos isManager() para simplificar
        // Los nuevos campos (probability, signing_date, processing_date)
        // se cargarán automáticamente aquí, ya que son parte del modelo Offer.
        $query = Offer::with(['package', 'user.team','client'])->latest();

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

        $probabilityOptions = [0, 25, 50, 75, 90, 100]; // <-- AÑADIDO

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
            'clients' => $clients, // Pasamos clientes filtrados
            'initialClientId' => $newClientId ? (int)$newClientId : null, // Asegurar que sea int o null
            'probabilityOptions' => $probabilityOptions, // <-- AÑADIDO
        ]);
    }

    public function store(Request $request)
    {
        // --- INICIO CÓDIGO MODIFICADO: Validación de additional_internet_lines ---
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'package_id' => 'required|exists:packages,id',
            'summary' => 'required|array',
            'lines' => 'present|array',
            'internet_addon_id' => 'nullable|exists:addons,id',
            'additional_internet_lines' => 'present|array',
            'additional_internet_lines.*.addon_id' => [ // Validar cada objeto en el array
                'required', // El addon_id es requerido si el objeto existe
                Rule::exists('addons', 'id')->where(function ($query) {
                    $query->where('type', 'internet_additional'); // Asegurar que sea del tipo correcto
                }),
            ],
            'additional_internet_lines.*.has_ip_fija' => 'required|boolean', // Validar el nuevo campo
            'centralita' => 'present|array',
            'tv_addons' => 'nullable|array',
            'tv_addons.*' => 'exists:addons,id',
            'is_ip_fija_selected' => 'nullable|boolean',
            'probability' => 'nullable|integer|in:0,25,50,75,90,100',
            'signing_date' => 'nullable|date',
            'processing_date' => 'nullable|date',
        ]);
        // --- FIN CÓDIGO MODIFICADO ---


        try {
            DB::transaction(function () use ($validated, $request) {
                $offer = Offer::create([
                    'package_id' => $validated['package_id'],
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'user_id' => $request->user()->id,
                    'probability' => $validated['probability'] ?? null,
                    'signing_date' => $validated['signing_date'] ?? null,
                    'processing_date' => $validated['processing_date'] ?? null,
                ]);

                foreach ($validated['lines'] as $lineData) {
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
                    ]);
                }

                $addonsToSync = [];
                // --- INICIO CÓDIGO MODIFICADO: Lógica IP Fija Principal y Adicionales ---
                $ipFijaAddon = \App\Models\Addon::where('name', 'IP Fija')->first();
                $ipFijaQuantity = 0;

                // Contar IP Fija principal
                if (!empty($validated['is_ip_fija_selected'])) {
                    $ipFijaQuantity++;
                }

                // Contar IP Fijas adicionales
                if (!empty($validated['additional_internet_lines'])) {
                    foreach ($validated['additional_internet_lines'] as $internetLine) {
                        if (!empty($internetLine['has_ip_fija']) && $internetLine['has_ip_fija'] === true) {
                            $ipFijaQuantity++;
                        }
                    }
                }

                // Sincronizar addon IP Fija si la cantidad es mayor que 0
                if ($ipFijaAddon && $ipFijaQuantity > 0) {
                    $addonsToSync[$ipFijaAddon->id] = ['quantity' => $ipFijaQuantity];
                }
                // --- FIN CÓDIGO MODIFICADO ---


                // Lógica para sincronizar otros addons (Internet, Centralita, TV)
                if (!empty($validated['internet_addon_id'])) {
                    $addonsToSync[$validated['internet_addon_id']] = ['quantity' => 1];
                }
                // Sincronizar las líneas adicionales de internet (sin IP Fija aquí, ya se contó antes)
                if (!empty($validated['additional_internet_lines'])) {
                    foreach($validated['additional_internet_lines'] as $internetLine) {
                         if (!empty($internetLine['addon_id'])) {
                             $addonsToSync[$internetLine['addon_id']] = ['quantity' => ($addonsToSync[$internetLine['addon_id']]['quantity'] ?? 0) + 1];
                         }
                    }
                }
                $centralitaData = $validated['centralita'];
                if (!empty($centralitaData['id'])) {
                    $addonsToSync[$centralitaData['id']] = ['quantity' => 1];
                }
                if (!empty($centralitaData['operadora_automatica_selected']) && !empty($centralitaData['operadora_automatica_id'])) {
                    $addonsToSync[$centralitaData['operadora_automatica_id']] = ['quantity' => 1];
                }
                if (!empty($centralitaData['extensions'])) {
                    foreach ($centralitaData['extensions'] as $ext) {
                        if (!empty($ext['addon_id']) && !empty($ext['quantity']) && $ext['quantity'] > 0) {
                            $addonsToSync[$ext['addon_id']] = ['quantity' => ($addonsToSync[$ext['addon_id']]['quantity'] ?? 0) + $ext['quantity']];
                        }
                    }
                }
                if (!empty($validated['tv_addons'])) {
                    foreach ($validated['tv_addons'] as $tvAddonId) {
                        $addonsToSync[$tvAddonId] = ['quantity' => 1];
                    }
                }

                $offer->addons()->sync($addonsToSync);
            });

            return redirect()->route('offers.index')->with('success', 'Oferta guardada correctamente.');

        } catch (\Exception $e) {
             \Log::error('Error storing offer: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()); // Loguear error con más detalle
             // Considerar mostrar un error más específico en desarrollo
             return back()->withInput()->with('error', 'Error al guardar la oferta. Revisa los datos e inténtalo de nuevo.');
        }
    }

    public function edit(Offer $offer)
    {
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

        // Cargar relaciones necesarias para la edición
        $offer->load(['lines', 'addons', 'client']); // Añadido 'client'

        // Cargar detalles del terminal para cada línea (como en show y pdf)
        $offer->lines->each(function ($line) {
            if ($line->package_terminal_id) {
                $pivotData = DB::table('package_terminal')->find($line->package_terminal_id);
                if ($pivotData) {
                    $pivotData->terminal = Terminal::find($pivotData->terminal_id);
                    // Usamos 'terminal_pivot' para consistencia con tu Vue
                    $line->terminal_pivot = $pivotData;
                } else {
                     $line->terminal_pivot = null; // Asegurar que sea null si no se encuentra
                }
            } else {
                $line->terminal_pivot = null;
            }
        });

        // --- INICIO CÓDIGO MODIFICADO: Preparar datos para IP Fijas adicionales ---
        // Necesitamos saber cuántas IP Fijas hay asociadas en total
        $ipFijaAddon = $offer->addons()->where('name', 'IP Fija')->first();
        $totalIpFijaQuantity = $ipFijaAddon ? $ipFijaAddon->pivot->quantity : 0;
        $mainIpFijaSelected = $totalIpFijaQuantity > 0; // Asumimos que si hay >0, la principal está (simplificación)

        // Contar cuántas líneas adicionales hay
        $additionalLinesCount = $offer->addons()->where('type', 'internet_additional')->count();

        // Calcular cuántas IP fijas adicionales deberían estar marcadas
        // (Esto es una estimación si no guardamos el estado individualmente)
        $additionalIpFijaCount = max(0, $totalIpFijaQuantity - ($mainIpFijaSelected ? 1 : 0));

        // Añadir 'has_ip_fija' a los datos pasados a Vue
        // Marcamos las primeras 'additionalIpFijaCount' líneas adicionales como true
        $additionalInternetLinesData = $offer->addons()
                                            ->where('type', 'internet_additional')
                                            ->get()
                                            ->map(function ($addon, $index) use ($additionalIpFijaCount) {
                                                return [
                                                    'id' => $addon->id + microtime(true) + $index, // ID único para Vue
                                                    'addon_id' => $addon->id,
                                                    'has_ip_fija' => $index < $additionalIpFijaCount, // Marcar las primeras N
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
             // --- INICIO CÓDIGO MODIFICADO: Pasar datos preparados ---
            'initialAdditionalInternetLines' => $additionalInternetLinesData, // Datos preparados
            'initialMainIpFijaSelected' => $mainIpFijaSelected,            // Estado de la IP Fija principal
             // --- FIN CÓDIGO MODIFICADO ---
        ]);
    }


    public function update(Request $request, Offer $offer)
    {
         $user = Auth::user();
         $clientId = $request->input('client_id');
         $canAccessClient = false;
         // Comprobación de seguridad: ¿puede el usuario actual usar este cliente?
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

        // --- INICIO CÓDIGO MODIFICADO: Validación de additional_internet_lines ---
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
             'centralita' => 'present|array',
             'tv_addons' => 'nullable|array',
             'tv_addons.*' => 'exists:addons,id',
             'is_ip_fija_selected' => 'nullable|boolean',
             'probability' => 'nullable|integer|in:0,25,50,75,90,100',
             'signing_date' => 'nullable|date',
             'processing_date' => 'nullable|date',
         ]);
        // --- FIN CÓDIGO MODIFICADO ---


        try {
            DB::transaction(function () use ($validated, $offer) {
                $offer->update([
                    'package_id' => $validated['package_id'],
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'probability' => $validated['probability'] ?? null,
                    'signing_date' => $validated['signing_date'] ?? null,
                    'processing_date' => $validated['processing_date'] ?? null,
                    // user_id no se actualiza
                ]);

                // Borrar y recrear líneas
                $offer->lines()->delete();
                foreach ($validated['lines'] as $lineData) {
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
                    ]);
                }

                // Sincronizar addons
                $addonsToSync = [];
                // --- INICIO CÓDIGO MODIFICADO: Lógica IP Fija Principal y Adicionales (igual que en store) ---
                $ipFijaAddon = \App\Models\Addon::where('name', 'IP Fija')->first();
                $ipFijaQuantity = 0;

                if (!empty($validated['is_ip_fija_selected'])) {
                    $ipFijaQuantity++;
                }
                if (!empty($validated['additional_internet_lines'])) {
                    foreach ($validated['additional_internet_lines'] as $internetLine) {
                        if (!empty($internetLine['has_ip_fija']) && $internetLine['has_ip_fija'] === true) {
                            $ipFijaQuantity++;
                        }
                    }
                }
                if ($ipFijaAddon && $ipFijaQuantity > 0) {
                    $addonsToSync[$ipFijaAddon->id] = ['quantity' => $ipFijaQuantity];
                }
                // --- FIN CÓDIGO MODIFICADO ---

                 if (!empty($validated['internet_addon_id'])) {
                     $addonsToSync[$validated['internet_addon_id']] = ['quantity' => 1];
                 }
                // Sincronizar líneas adicionales (sin IP fija aquí)
                 if (!empty($validated['additional_internet_lines'])) {
                     foreach ($validated['additional_internet_lines'] as $internetLine) {
                         if (!empty($internetLine['addon_id'])) {
                             $addonsToSync[$internetLine['addon_id']] = ['quantity' => ($addonsToSync[$internetLine['addon_id']]['quantity'] ?? 0) + 1];
                         }
                     }
                 }
                 $centralitaData = $validated['centralita'];
                 if (!empty($centralitaData['id'])) {
                     $addonsToSync[$centralitaData['id']] = ['quantity' => 1];
                 }
                 if (!empty($centralitaData['operadora_automatica_selected']) && !empty($centralitaData['operadora_automatica_id'])) {
                     $addonsToSync[$centralitaData['operadora_automatica_id']] = ['quantity' => 1];
                 }
                 if (!empty($centralitaData['extensions'])) {
                     foreach ($centralitaData['extensions'] as $ext) {
                         if (!empty($ext['addon_id']) && !empty($ext['quantity']) && $ext['quantity'] > 0) {
                             $addonsToSync[$ext['addon_id']] = ['quantity' => ($addonsToSync[$ext['addon_id']]['quantity'] ?? 0) + $ext['quantity']];
                         }
                     }
                 }
                 if (!empty($validated['tv_addons'])) {
                     foreach ($validated['tv_addons'] as $tvAddonId) {
                         $addonsToSync[$tvAddonId] = ['quantity' => 1];
                     }
                 }

                $offer->addons()->sync($addonsToSync);
            });

            return redirect()->route('offers.index')->with('success', '¡Oferta actualizada correctamente!');

        } catch (\Exception $e) {
            \Log::error('Error updating offer '.$offer->id.': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
            return back()->withInput()->with('error', 'No se pudo actualizar la oferta. Revisa los datos.');
        }
    }


    public function show(Offer $offer)
    {
        // Cargar todas las relaciones necesarias
        $offer->load(['package.addons', 'user.team', 'lines', 'addons', 'client']);

        // Cargar detalles del terminal para cada línea
        $offer->lines->each(function ($line) {
            if ($line->package_terminal_id) {
                // Usamos una consulta directa a la tabla pivote y al terminal
                $terminalData = DB::table('package_terminal')
                    ->join('terminals', 'package_terminal.terminal_id', '=', 'terminals.id')
                    ->where('package_terminal.id', $line->package_terminal_id)
                    ->select('terminals.brand', 'terminals.model', 'package_terminal.duration_months')
                    ->first();
                $line->terminal_details = $terminalData; // Añadimos como propiedad dinámica
            } else {
                $line->terminal_details = null;
            }
        });

        return Inertia::render('Offers/Show', [
            'offer' => $offer,
        ]);
    }


    public function generatePDF(Offer $offer)
    {
        // Aumentar tiempo de ejecución
        set_time_limit(300);

        // Cargar relaciones
        $offer->load(['package', 'user', 'lines', 'addons', 'client']);

        // Cargar detalles del terminal
        $offer->lines->each(function ($line) {
            if ($line->package_terminal_id) {
                $terminalData = DB::table('package_terminal')
                    ->join('terminals', 'package_terminal.terminal_id', '=', 'terminals.id')
                    ->where('package_terminal.id', $line->package_terminal_id)
                    ->select('terminals.brand', 'terminals.model', 'package_terminal.duration_months')
                    ->first();
                $line->terminal_details = $terminalData;
            } else {
                $line->terminal_details = null;
            }
        });

        // Cargar la vista y generar el PDF
        $pdf = PDF::loadView('pdfs.offer_pdf', compact('offer'));

        // Generar nombre de archivo
        $clientName = $offer->client ? Str::slug($offer->client->name) : 'sin-cliente';
        $fileName = 'oferta-' . $offer->id . '-' . $clientName . '.pdf';

        // Descargar el PDF
        return $pdf->download($fileName);
    }

    public function destroy(Offer $offer)
    {
         // OPCIONAL: Añadir autorización
         // if (Auth::user()->cannot('delete', $offer)) {
         //     abort(403);
         // }

        try {
            DB::transaction(function () use ($offer) {
                // Eliminar relaciones dependientes
                 $offer->lines()->delete();
                 $offer->addons()->detach();
                // Borrar la oferta principal
                $offer->delete();
            });
            return redirect()->route('offers.index')->with('success', 'Oferta eliminada correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error deleting offer '.$offer->id.': '.$e->getMessage());
             return redirect()->route('offers.index')->with('error', 'No se pudo eliminar la oferta. Puede tener elementos asociados.');
        }
    }
 public function exportFunnel(Request $request)
    {
        $user = Auth::user();

        // --- Paso 1: Comprobación de Permiso MÁS FLEXIBLE ---
        $allowedRoles = ['admin', 'jefe de ventas', 'team_lead'];
        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'No tienes permiso para realizar esta exportación.');
        }
        // --- Fin Comprobación de Permiso ---


        // --- Paso 2: Lógica de Consulta Ajustada ---
        $query = Offer::with(['package', 'user.team','client'])
                        ->withCount('lines')
                        ->latest();

        if ($user->role === 'jefe de ventas' || $user->role === 'team_lead') {
             if ($user->team_id) {
                 $teamMemberIds = User::where('team_id', $user->team_id)->pluck('id');
                 $teamMemberIds->push($user->id);
                 $query->whereIn('user_id', $teamMemberIds->unique());
             } else {
                  // Jefe/Lead sin equipo, solo ve los suyos
                  $query->where('user_id', $user->id);
             }
        }
        // Admin ve todo

        $offersToExport = $query->get();
        // --- Fin Lógica de Consulta ---


        // --- Generación del CSV ---
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
                'Paquete', 'Precio Final (€)', 'Probabilidad (%)', 'Fecha Firma',
                'Fecha Tramitación', 'Fecha Creación', 'Num. Líneas Móviles',
            ], ';');

            foreach ($offersToExport as $offer) {
                fputcsv($file, [
                    $offer->id,
                    $offer->client?->name ?? 'N/A',
                    $offer->client?->cif_nif ?? 'N/A',
                    $offer->user?->name ?? 'N/A',
                    $offer->user?->team?->name ?? 'N/A',
                    $offer->package?->name ?? 'N/A',
                    number_format($offer->summary['finalPrice'] ?? 0, 2, '.', ''),
                    $offer->probability ?? '',
                    $offer->signing_date ? $offer->signing_date->format('Y-m-d') : '',
                    $offer->processing_date ? $offer->processing_date->format('Y-m-d') : '',
                    $offer->created_at ? $offer->created_at->format('Y-m-d H:i:s') : '',
                    $offer->lines_count ?? 0,
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

} // Fin de la clase OfferController