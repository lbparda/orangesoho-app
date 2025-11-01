<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    /**
     * Muestra el listado de paquetes.
     */
    public function index()
    {
        return Inertia::render('Admin/Packages/Index', [
            // Mapeamos los datos para asegurarnos de que solo pasamos lo que existe
            'packages' => Package::all()->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'base_price' => $package->base_price, // <-- Usamos base_price
                ];
            }),
        ]);
    }

    /**
     * Muestra el formulario de edición de un paquete.
     */
    public function edit(Package $package)
    {
        // Versión simple: solo pasamos el paquete.
        // Ya no necesitamos 'typeOptions' o 'statusOptions'
        return Inertia::render('Admin/Packages/Edit', [
            'package' => $package,
        ]);
    }

    /**
     * Actualiza un paquete en la base de datos.
     */
    public function update(Request $request, Package $package)
    {
        // --- INICIO: SECCIÓN CORREGIDA Y SIMPLIFICADA ---
        // Validamos SÓLO los campos que existen en la BBDD y que queremos editar
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'], // <-- Usamos base_price
        ]);
        // --- FIN: SECCIÓN CORREGIDA ---

        $package->update($validated);

        return redirect()->route('admin.packages.index')
                         ->with('success', 'Paquete actualizado correctamente.');
    }
}
