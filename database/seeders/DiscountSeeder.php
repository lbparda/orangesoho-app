<?php

namespace Database\Seeders;

use App\Models\Discount;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        // --- DESCUENTOS PARA PAQUETES 'NEGOCIO Extra 1, 3, 5' ---
        Discount::create([
            'name' => '30% Portabilidad con VAP (No Movistar) - Grupo 1',
            'percentage' => 30, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => true, 'excluded_operators' => ['Movistar'],
                'package_names' => ['NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

        Discount::create([
            'name' => '20% Portabilidad sin VAP (No Movistar) - Grupo 1',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => false, 'excluded_operators' => ['Movistar'],
                'package_names' => ['NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

        Discount::create([
            'name' => '20% Portabilidad Movistar con VAP o Alta Nueva - Grupo 1',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_vap' => true,
                'source_operators' => ['Movistar', 'new_customer', 'migration'],
                'package_names' => ['NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

        // --- NUEVO: DESCUENTOS PARA PAQUETES 'NEGOCIO Extra 10, 20' ---
        Discount::create([
            'name' => '20% con VAP (No Movistar) - Grupo 2',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => true, 'excluded_operators' => ['Movistar'],
                'package_names' => ['NEGOCIO Extra 10', 'NEGOCIO Extra 20']
            ]
        ]);

        Discount::create([
            'name' => '10% con VAP (Movistar) - Grupo 2',
            'percentage' => 10, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => true, 'source_operators' => ['Movistar'],
                'package_names' => ['NEGOCIO Extra 10', 'NEGOCIO Extra 20']
            ]
        ]);
    }
}
