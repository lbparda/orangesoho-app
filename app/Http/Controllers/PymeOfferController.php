<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PymePackage;
use App\Models\PymeO2oDiscount;
use App\Models\PymeAddon; // Importante: Importar el modelo PymeAddon
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PymeOfferController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();

        // 1. Cargar Clientes
        $clientsQuery = Client::query();
        if ($user->isManager()) {
            $teamMembersIds = User::where('team_id', $user->team_id)->pluck('id');
            $clientsQuery->whereIn('user_id', $teamMembersIds);
        } elseif ($user->role === 'user') {
            $clientsQuery->where('user_id', $user->id);
        }
        $clients = $clientsQuery->select('id', 'name', 'cif_nif')->orderBy('name')->get();

        // 2. Cargar Paquetes PYME
        $pymePackages = PymePackage::with(['terminalsVap', 'terminalsSub'])
            ->orderBy('base_price', 'asc')
            ->get();

        // 3. Cargar Descuentos O2O
        $pymeO2oDiscounts = PymeO2oDiscount::where('is_active', true)
            ->orderBy('percentage', 'asc')
            ->get();

        // 4. CARGAR ADDONS (Definición de variables)
        // Definimos $allPymeAddons PRIMERO para evitar el error "Undefined variable"
        $allPymeAddons = PymeAddon::all();
        
        // Ahora filtramos usando la variable ya definida
        $centralitaMobileAddons = $allPymeAddons->where('type', 'centralita_mobile')->values();
        $centralitaExtensions = $allPymeAddons->where('type', 'centralita_extension')->values();
        $centralitaFeatures = $allPymeAddons->where('type', 'centralita_feature')->values();
        $internetFeatures = $allPymeAddons->where('type', 'internet_feature')->values();

        return Inertia::render('Offers/Create', [
            'initialMode' => 'pyme',
            'clients' => $clients,
            'pymePackages' => $pymePackages,
            'pymeO2oDiscounts' => $pymeO2oDiscounts,
            
            // Pasar los datos de Addons a la vista
            'centralitaMobileAddons' => $centralitaMobileAddons,
            'centralitaExtensions' => $centralitaExtensions,
            'centralitaFeatures' => $centralitaFeatures,
            'fiberFeatures' => $internetFeatures,
            'allAddons' => $allPymeAddons,

            'auth' => ['user' => $user->load('team')],
            
            // Props vacías para compatibilidad
            'packages' => [],
            'discounts' => [],
            'operators' => [],
            'portabilityCommission' => 0,
            'additionalInternetAddons' => [],
            'probabilityOptions' => [],
            'portabilityExceptions' => [],
        ]);
    }

    public function store(Request $request)
    {
        // Lógica de guardado pendiente
        return redirect()->route('offers.index');
    }
}