<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Discount;
use App\Models\Offer;
use App\Models\Package;
use App\Models\Terminal; // <-- Importante tener este 'use'
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Offer::with(['package', 'user.team'])->latest();

        switch ($user->role) {
            case 'admin':
                break;
            case 'team_lead':
                if ($user->team_id) {
                    $teamMemberIds = User::where('team_id', $user->team_id)->pluck('id');
                    $query->whereIn('user_id', $teamMemberIds);
                } else {
                    $query->where('user_id', $user->id);
                }
                break;
            default: // 'user' role
                $query->where('user_id', $user->id);
                break;
        }

        return Inertia::render('Offers/Index', [
            'offers' => $query->paginate(10)
        ]);
    }

    public function create()
    {
        $packages = Package::with(['addons', 'o2oDiscounts', 'terminals'])->get();
        $discounts = Discount::all();
        $operators = ['Movistar', 'Vodafone', 'Orange', 'MasMovil', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 5.00); 
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();

        return Inertia::render('Offers/Create', [
            'packages' => $packages,
            'discounts' => $discounts,
            'operators' => $operators,
            'portabilityCommission' => $portabilityCommission,
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'auth' => ['user' => auth()->user()->load('team')],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'summary' => 'required|array',
            'lines' => 'present|array',
            'internet_addon_id' => 'nullable|exists:addons,id',
            'additional_internet_lines' => 'present|array',
            'centralita' => 'present|array',
            'tv_addons' => 'nullable|array',
            'tv_addons.*' => 'exists:addons,id'
        ]);

        DB::transaction(function () use ($validated, $request) {
            $offer = Offer::create([
                'package_id' => $validated['package_id'],
                'summary' => $validated['summary'],
                'user_id' => $request->user()->id,
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
            if ($validated['internet_addon_id']) {
                $addonsToSync[$validated['internet_addon_id']] = ['quantity' => 1];
            }
            foreach($validated['additional_internet_lines'] as $internetLine) {
                 if (isset($addonsToSync[$internetLine['addon_id']])) {
                     $addonsToSync[$internetLine['addon_id']]['quantity']++;
                 } else {
                     $addonsToSync[$internetLine['addon_id']] = ['quantity' => 1];
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
                    if (isset($addonsToSync[$ext['addon_id']])) {
                        $addonsToSync[$ext['addon_id']]['quantity'] += $ext['quantity'];
                    } else {
                        $addonsToSync[$ext['addon_id']] = ['quantity' => $ext['quantity']];
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
    }

    // --- MÉTODO 'edit' CORREGIDO ---
    public function edit(Offer $offer)
    {
        $packages = Package::with([
            'addons', 
            'o2oDiscounts', 
            'terminals' => fn($query) => $query->select('terminals.*', 'package_terminal.id as pivot_id', 'package_terminal.duration_months', 'package_terminal.initial_cost', 'package_terminal.monthly_cost')
        ])->get();
        $discounts = Discount::all();
        $operators = ['Movistar', 'Vodafone', 'Orange', 'MasMovil', 'Otros'];
        $portabilityCommission = config('commissions.portability_extra', 5.00); 
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();
        
        // 1. Cargamos las relaciones que sí existen en los modelos
        $offer->load(['lines', 'addons']);

        // 2. Para cada línea, cargamos manualmente la información del terminal (igual que en el método 'show')
        $offer->lines->each(function ($line) {
            if ($line->package_terminal_id) {
                // Buscamos la fila completa en la tabla pivote
                $pivotData = DB::table('package_terminal')->find($line->package_terminal_id);
                if ($pivotData) {
                    // Buscamos el modelo del terminal asociado
                    $pivotData->terminal = Terminal::find($pivotData->terminal_id);
                    // Adjuntamos esta información a la línea, llamándola 'terminal_pivot' para que Vue la entienda
                    $line->terminal_pivot = $pivotData;
                }
            } else {
                $line->terminal_pivot = null;
            }
        });

        return Inertia::render('Offers/Edit', [
            'offer' => $offer,
            'packages' => $packages,
            'discounts' => $discounts,
            'operators' => $operators,
            'portabilityCommission' => $portabilityCommission,
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'auth' => ['user' => auth()->user()->load('team')],
        ]);
    }

    public function update(Request $request, Offer $offer)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'summary' => 'required|array',
            'lines' => 'present|array',
            'internet_addon_id' => 'nullable|exists:addons,id',
            'additional_internet_lines' => 'present|array',
            'centralita' => 'present|array',
            'tv_addons' => 'nullable|array',
            'tv_addons.*' => 'exists:addons,id'
        ]);

        DB::transaction(function () use ($validated, $offer) {
            $offer->update([
                'package_id' => $validated['package_id'],
                'summary' => $validated['summary'],
            ]);

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

            $addonsToSync = [];
            if ($validated['internet_addon_id']) {
                $addonsToSync[$validated['internet_addon_id']] = ['quantity' => 1];
            }
            foreach($validated['additional_internet_lines'] as $internetLine) {
                 if (isset($addonsToSync[$internetLine['addon_id']])) {
                     $addonsToSync[$internetLine['addon_id']]['quantity']++;
                 } else {
                     $addonsToSync[$internetLine['addon_id']] = ['quantity' => 1];
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
                    if (isset($addonsToSync[$ext['addon_id']])) {
                        $addonsToSync[$ext['addon_id']]['quantity'] += $ext['quantity'];
                    } else {
                        $addonsToSync[$ext['addon_id']] = ['quantity' => $ext['quantity']];
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
    }

    public function show(Offer $offer)
    {
        $offer->load(['package.addons', 'user.team', 'lines', 'addons']);

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

        return Inertia::render('Offers/Show', [
            'offer' => $offer,
        ]);
    }
}