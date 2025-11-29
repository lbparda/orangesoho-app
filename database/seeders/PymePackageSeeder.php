<?php

namespace Database\Seeders;

use App\Models\PymePackage; // Usamos el nuevo modelo
use Illuminate\Database\Seeder;

class PymePackageSeeder extends Seeder
{
    public function run(): void
    {
       
        $packages = [
            [
                'name' => 'EMPRESAS VOZ', 
                'base_price' => 6.00,
                'commission_optima' => 6.00, 'commission_custom' => 4.00,
                'bonus_cp_24' => 5.00, 'bonus_cp_36' => 17.00,
                'bonus_cp_24_terminal' => 43.00, 'bonus_cp_36_terminal' => 102.00 
            ],         
            [
                'name' => 'EMPRESAS 5', 
                'base_price' => 15.00,
                'commission_optima' =>130.00, 'commission_custom' => 20.00,
                'bonus_cp_24' => 12.00, 'bonus_cp_36' => 42.00,
                'bonus_cp_24_terminal' => 43.00, 'bonus_cp_36_terminal' => 102.00 
            ],
            [
                'name' => 'EMPRESAS 20', 
                'base_price' => 23.00,
                'commission_optima' => 200.00, 'commission_custom' => 30.00,
                'bonus_cp_24' => 18.00, 'bonus_cp_36' => 64.00, 
                'bonus_cp_24_terminal' => 43.00, 'bonus_cp_36_terminal' => 102.00
            ],
            [
                'name' => 'EMPRESAS ILIMITADA', 
                'base_price' => 29.00,
                'commission_optima' => 250.00, 'commission_custom' => 150.00,
                'bonus_cp_24' => 23.00, 'bonus_cp_36' => 81.00,
                'bonus_cp_24_terminal' => 43.00, 'bonus_cp_36_terminal' => 102.00

            ],
            [
                'name' => 'EMPRESAS INTERNACIONAL', 
                'base_price' => 48.00,
                'commission_optima' => 415.00, 'commission_custom' => 170.00,
                'bonus_cp_24' => 38.00, 'bonus_cp_36' => 134.00, 
                'bonus_cp_24_terminal' => 43.00, 'bonus_cp_36_terminal' => 102.00
            ],
        ];

        foreach ($packages as $pkg) {
            PymePackage::updateOrCreate(
                ['name' => $pkg['name']], 
                [
                    'base_price' => $pkg['base_price'],
                    'commission_optima' => $pkg['commission_optima'],
                    'commission_custom' => $pkg['commission_custom'],
                    'commission_porta' => 30.00,
                    'bonus_cp_24' => $pkg['bonus_cp_24'],
                    'bonus_cp_36' => $pkg['bonus_cp_36'],
                    'bonus_cp_24_terminal' => $pkg['bonus_cp_24_terminal'],
                    'bonus_cp_36_terminal' => $pkg['bonus_cp_36_terminal'],
                ]
            );
        }
    }
}