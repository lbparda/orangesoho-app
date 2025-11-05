<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    /**
     * Muestra el listado de descuentos.
     */
    public function index()
    {
        return Inertia::render('Admin/Discounts/Index', [
            'discounts' => Discount::all()->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'percentage' => $discount->percentage,
                    'duration_months' => $discount->duration_months,
                ];
            }),
        ]);
    }

    /**
     * Muestra el formulario de edición de un descuento.
     */
    public function edit(Discount $discount)
    {
        // --- INICIO: ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
        // En lugar de pasar el modelo $discount directamente,
        // creamos un array manualmente para asegurarnos
        // de que TODOS los campos se envían a la vista.
        return Inertia::render('Admin/Discounts/Edit', [
            'discount' => [
                'id' => $discount->id,
                'name' => $discount->name,
                'percentage' => $discount->percentage,
                'duration_months' => $discount->duration_months,
                'conditions' => $discount->conditions,
            ],
        ]);
        // --- FIN: CORRECCIÓN ---
    }

    /**
     * Actualiza un descuento en la base de datos.
     */
    public function update(Request $request, Discount $discount)
    {
        // Validamos los campos de tu tabla
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'duration_months' => ['required', 'integer', 'min:0'],
            // Validamos que 'conditions' sea un array (objeto JSON) o nulo.
            'conditions' => ['nullable', 'array'],
        ]);

        $discount->update($validated);

        return redirect()->route('admin.discounts.index')
                         ->with('success', 'Descuento actualizado correctamente.');
    }
}