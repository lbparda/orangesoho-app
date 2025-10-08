<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Discount;
use App\Models\Offer;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // La ruta ya está protegida por middleware, por lo que $user siempre existirá aquí.
        $query = Offer::with(['package', 'user'])->latest();

        if ($user->team_id) {
            $teamMemberIds = User::where('team_id', $user->team_id)->pluck('id');
            $query->whereIn('user_id', $teamMemberIds);
        } else {
            $query->where('user_id', $user->id);
        }

        return Inertia::render('Offers/Index', [
            'offers' => $query->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'summary' => 'required|array',
            'lines' => 'present|array',
            'internet_addon_id' => 'nullable|exists:addons,id',
            'additional_internet_lines' => 'present|array',
            'centralita' => 'present|array',
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
            if ($centralitaData['id']) {
                $addonsToSync[$centralitaData['id']] = ['quantity' => 1];
            }
            if ($centralitaData['operadora_automatica_selected'] && $centralitaData['operadora_automatica_id']) {
                $addonsToSync[$centralitaData['operadora_automatica_id']] = ['quantity' => 1];
            }
            foreach ($centralitaData['extensions'] as $ext) {
                if (isset($addonsToSync[$ext['addon_id']])) {
                    $addonsToSync[$ext['addon_id']]['quantity'] += $ext['quantity'];
                } else {
                    $addonsToSync[$ext['addon_id']] = ['quantity' => $ext['quantity']];
                }
            }
            $offer->addons()->sync($addonsToSync);
        });

        return redirect()->route('offers.index')->with('success', 'Oferta guardada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Offer $offer)
    {
        $offer->load(['package.addons', 'user', 'lines', 'addons']);

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