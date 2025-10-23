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
        $operators = ['Movistar', 'Vodafone', 'MasMovil', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 5.00);
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();

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
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'auth' => ['user' => auth()->user()->load('team')],
            'clients' => $clients, // Pasamos clientes filtrados
            'initialClientId' => $newClientId ? (int)$newClientId : null, // Asegurar que sea int o null
            'probabilityOptions' => $probabilityOptions, // <-- AÑADIDO
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
            'centralita' => 'present|array',
            'tv_addons' => 'nullable|array',
            'tv_addons.*' => 'exists:addons,id',
            'probability' => 'nullable|integer|in:0,25,50,75,90,100', // <-- AÑADIDO
            'signing_date' => 'nullable|date',                     // <-- AÑADIDO
            'processing_date' => 'nullable|date',                  // <-- AÑADIDO
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $offer = Offer::create([
                    'package_id' => $validated['package_id'],
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'user_id' => $request->user()->id,
                    'probability' => $validated['probability'] ?? null,         // <-- AÑADIDO
                    'signing_date' => $validated['signing_date'] ?? null,       // <-- AÑADIDO
                    'processing_date' => $validated['processing_date'] ?? null, // <-- AÑADIDO
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
                // Lógica para sincronizar addons (Internet, Centralita, TV)
                if (!empty($validated['internet_addon_id'])) {
                    $addonsToSync[$validated['internet_addon_id']] = ['quantity' => 1];
                }
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
             \Log::error('Error storing offer: '.$e->getMessage()); // Loguear error
             // Considerar mostrar un error más específico en desarrollo
             return back()->withInput()->with('error', 'Error al guardar la oferta. Inténtalo de nuevo.');
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
        $operators = ['Movistar', 'Vodafone', 'MasMovil', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 5.00);
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();

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

        $probabilityOptions = [0, 25, 50, 75, 90, 100]; // <-- AÑADIDO

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

        return Inertia::render('Offers/Edit', [
            'offer' => $offer, // 'probability', 'signing_date', 'processing_date' ya vienen aquí
            'packages' => $packages,
            'discounts' => $discounts,
            'operators' => $operators,
            'portabilityCommission' => $portabilityCommission,
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'auth' => ['user' => auth()->user()->load('team')],
            'clients' => $clients, // Pasamos clientes filtrados
            'probabilityOptions' => $probabilityOptions, // <-- AÑADIDO
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

         // Validación (igual que en store, más los nuevos campos)
         $validated = $request->validate([
              'client_id' => 'required|exists:clients,id',
              'package_id' => 'required|exists:packages,id',
              'summary' => 'required|array',
              'lines' => 'present|array',
              'internet_addon_id' => 'nullable|exists:addons,id',
              'additional_internet_lines' => 'present|array',
              'centralita' => 'present|array',
              'tv_addons' => 'nullable|array',
              'tv_addons.*' => 'exists:addons,id',
              'probability' => 'nullable|integer|in:0,25,50,75,90,100', // <-- AÑADIDO
              'signing_date' => 'nullable|date',                     // <-- AÑADIDO
              'processing_date' => 'nullable|date',                  // <-- AÑADIDO
         ]);


        try {
            DB::transaction(function () use ($validated, $offer) {
                $offer->update([
                    'package_id' => $validated['package_id'],
                    'client_id' => $validated['client_id'],
                    'summary' => $validated['summary'],
                    'probability' => $validated['probability'] ?? null,         // <-- AÑADIDO
                    'signing_date' => $validated['signing_date'] ?? null,       // <-- AÑADIDO
                    'processing_date' => $validated['processing_date'] ?? null, // <-- AÑADIDO
                    // user_id no se actualiza, el creador es siempre el mismo
                ]);

                // Borrar y recrear líneas (más simple que actualizar una por una)
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

                // Sincronizar addons (igual que en store)
                $addonsToSync = [];
                 if (!empty($validated['internet_addon_id'])) {
                    $addonsToSync[$validated['internet_addon_id']] = ['quantity' => 1];
                 }
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
            \Log::error('Error updating offer '.$offer->id.': '.$e->getMessage());
            // Mostrar error detallado en desarrollo, genérico en producción
            // return back()->withInput()->with('error', 'Error al actualizar la oferta: ' . $e->getMessage());
            return back()->withInput()->with('error', 'No se pudo actualizar la oferta. Revisa los datos.');
        }
    }


    public function show(Offer $offer)
    {
        // Cargar todas las relaciones necesarias
        // Los nuevos campos ya están en $offer
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
        // Aumentar tiempo de ejecución si la generación es lenta
        set_time_limit(300); // 5 minutos

        // Cargar relaciones
        // Los nuevos campos ya están en $offer
        $offer->load(['package', 'user', 'lines', 'addons', 'client']);

        // Cargar detalles del terminal (igual que en show)
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

        // Generar nombre de archivo seguro
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
                // Eliminar relaciones dependientes ANTES de borrar la oferta
                 $offer->lines()->delete(); // Borrar líneas
                 $offer->addons()->detach(); // Desvincular addons
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
        // Permitir si es admin, jefe de ventas O team_lead
        $allowedRoles = ['admin', 'jefe de ventas', 'team_lead']; // <-- Define los roles permitidos
        if (!in_array($user->role, $allowedRoles)) { // <-- Comprueba si el rol está en la lista
            abort(403, 'No tienes permiso para realizar esta exportación.');
        }
        // --- Fin Comprobación de Permiso ---


        // --- Paso 2: Lógica de Consulta Ajustada ---
        $query = Offer::with(['package', 'user.team','client'])
                      ->withCount('lines')
                      ->latest();

        // Aplicar filtros si es jefe de ventas O team_lead
        if ($user->role === 'jefe de ventas' || $user->role === 'team_lead') { // <-- Comprueba ambos roles
             if ($user->team_id) {
                $teamMemberIds = User::where('team_id', $user->team_id)->pluck('id');
                $teamMemberIds->push($user->id);
                $query->whereIn('user_id', $teamMemberIds->unique());
             } else {
                 // Jefe/Lead sin equipo, solo ve los suyos
                 $query->where('user_id', $user->id);
             }
        }
        // Admin ve todo (no entra en el 'if' anterior)

        $offersToExport = $query->get();
        // --- Fin Lógica de Consulta ---


        // --- Generación del CSV (sin cambios) ---
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
