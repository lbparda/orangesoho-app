<?php

namespace App\Http\Controllers;

// Declaraciones 'use' con el namespace correcto
use App\Models\Addon;
use App\Models\Discount;
use App\Models\O2oDiscount;
use App\Models\Package;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfferController extends Controller
{
    /**
     * Muestra la lista de ofertas guardadas.
     * (Esta función la desarrollaremos más adelante)
     */
    public function index()
    {
        // Lógica futura para ver las ofertas guardadas
    }

    /**
     * Muestra el formulario para crear una nueva oferta.
     */
    public function create()
    {
        // Cargamos los paquetes con TODAS sus relaciones (addons, descuentos, etc.)
        $packages = Package::with(['addons', 'o2oDiscounts', 'terminals'])->get();
       
        // Obtenemos el resto de datos necesarios para los desplegables
        $discounts = Discount::all();
        $operators = ['Movistar', 'Vodafone', 'Orange', 'MasMovil', 'Otros'];
        $terminals = Terminal::all();
        $portabilityCommission = config('commissions.portability_extra');
        $additionalInternetAddons = Addon::where('type', 'internet_additional')->get();
        // NUEVO: Buscamos los addons de las extensiones de centralita
        $centralitaExtensions = Addon::where('type', 'centralita_extension')->get();
        // Devolvemos la vista de Vue y le pasamos todos los datos como props
        return Inertia::render('Offers/Create', [
            'packages' => $packages,
            'discounts' => $discounts,
            'operators' => $operators,
            'terminals' => $terminals,
            'portabilityCommission' => $portabilityCommission,
            'additionalInternetAddons' => $additionalInternetAddons,
            'centralitaExtensions' => $centralitaExtensions, // <-- Pasamos la nueva prop
        ]);
    }

    /**
     * Guarda una nueva oferta en la base de datos.
     * (Esta función la desarrollaremos en el siguiente paso)
     */
    public function store(Request $request)
    {
        // Lógica futura para guardar el formulario
    }
}