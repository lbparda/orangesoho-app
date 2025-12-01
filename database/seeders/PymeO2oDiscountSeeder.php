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
        // Definimos los descuentos y sus penalizaciones (MERMAS) según permanencia.
        // ESTRUCTURA: ['Nombre', % Dto Cliente, Merma 12m, Merma 24m, Merma 36m]
        
        // ¡¡IMPORTANTE!!: Sustituye los 0 por los valores reales de tu tabla.
        $discounts = [
            ['name' => 'Sin O2O (0%)', 'percentage' => 0,  'p12' => 75,  'p24' => 50,  'p36' => 50],
            ['name' => 'O2O 5%',       'percentage' => 5,  'p12' => 75,  'p24' => 50,  'p36' => 50], 
            ['name' => 'O2O 10%',      'percentage' => 10, 'p12' => 75,  'p24' => 50,  'p36' => 50],
            ['name' => 'O2O 15%',      'percentage' => 15, 'p12' => 75,  'p24' => 50,  'p36' => 50],
            ['name' => 'O2O 20%',      'percentage' => 20, 'p12' => 75,  'p24' => 50,  'p36' => 50],
            ['name' => 'O2O 25%',      'percentage' => 25, 'p12' => 50,  'p24' => 50,  'p36' => 50],
            ['name' => 'O2O 30%',      'percentage' => 30, 'p12' => 50,  'p24' => 50,  'p36' => 50],
            ['name' => 'O2O 35%',      'percentage' => 35, 'p12' => 50,  'p24' => 60,  'p36' => 50],
            ['name' => 'O2O 40%',      'percentage' => 40, 'p12' => 50,  'p24' => 60,  'p36' => 50],
        ];

        foreach ($discounts as $discount) {
            PymeO2oDiscount::updateOrCreate(
                ['name' => $discount['name']], 
                [
                    'percentage' => $discount['percentage'],
                    'penalty_12m' => $discount['p12'],
                    'penalty_24m' => $discount['p24'],
                    'penalty_36m' => $discount['p36'],
                    'is_active' => true, 
                ]
            );
        }
    }
}