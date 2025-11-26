<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PymeO2oDiscount;

class PymeO2oDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Definimos los porcentajes de descuento O2O para PYMES
        $discounts = [
            ['name' => 'Sin O2O (0%)', 'percentage' => 0],
            ['name' => 'O2O 5%', 'percentage' => 5],
            ['name' => 'O2O 10%', 'percentage' => 10],
            ['name' => 'O2O 15%', 'percentage' => 15],
            ['name' => 'O2O 20%', 'percentage' => 20],
            ['name' => 'O2O 25%', 'percentage' => 25],
            ['name' => 'O2O 30%', 'percentage' => 30],
            ['name' => 'O2O 35%', 'percentage' => 35],
            ['name' => 'O2O 40%', 'percentage' => 40],
        ];

        foreach ($discounts as $discount) {
            // Usamos updateOrCreate para evitar duplicados.
            // Solo insertamos los campos que realmente existen en la tabla 'pyme_o2o_discounts'
            // definidos en tu migración y modelo: 'name', 'percentage' y 'is_active'.
            
            PymeO2oDiscount::updateOrCreate(
                ['name' => $discount['name']], // Buscamos por nombre
                [
                    'percentage' => $discount['percentage'],
                    'is_active' => true, // Activamos por defecto
                    // Eliminamos 'discount_amount', 'duration_months', 'type' porque no existen en esta tabla.
                ]
            );
        }
    }
}