<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PymePackage; // Importamos el modelo correcto
use App\Models\PymeO2oDiscount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PymeOfferController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();

        // 1. Clientes
        $clientsQuery = Client::query();
        if ($user->isManager()) {
            $teamMembersIds = User::where('team_id', $user->team_id)->pluck('id');
            $clientsQuery->whereIn('user_id', $teamMembersIds);
        } elseif ($user->role === 'user') {
            $clientsQuery->where('user_id', $user->id);
        }
        $clients = $clientsQuery->select('id', 'name', 'cif_nif')->orderBy('name')->get();

        // 2. Paquetes PYME (Leemos de la tabla 'pyme_packages')
        $pymePackages = PymePackage::orderBy('base_price', 'asc')->get();

        // 3. Descuentos PYME
        $pymeO2oDiscounts = PymeO2oDiscount::where('is_active', true)
            ->orderBy('percentage', 'asc')
            ->get();

        return Inertia::render('Offers/Create', [
            'initialMode' => 'pyme',
            'clients' => $clients,
            'pymePackages' => $pymePackages,
            'pymeO2oDiscounts' => $pymeO2oDiscounts,
            'auth' => ['user' => $user->load('team')],
            
            // Props vacías para SOHO (para evitar errores en el componente compartido)
            'packages' => [],
            'allAddons' => [],
            'discounts' => [],
            'operators' => [],
            'portabilityCommission' => 0,
            'additionalInternetAddons' => [],
            'centralitaExtensions' => [],
            'probabilityOptions' => [],
            'portabilityExceptions' => [],
            'fiberFeatures' => [],
        ]);
    }

    public function store(Request $request)
    {
        // Pendiente
        return redirect()->route('offers.index');
    }
}