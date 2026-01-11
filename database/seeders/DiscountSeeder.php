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
            'name' => '25% Portabilidad con VAP (No Movistar) - Grupo 1',
            'percentage' => 25, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => true, 'excluded_operators' => ['Movistar'],
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

        Discount::create([
            'name' => '15% Portabilidad sin VAP (No Movistar) - Grupo 1',
            'percentage' => 15, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => false, 'excluded_operators' => ['Movistar'],
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

        Discount::create([
            'name' => '20% Portabilidad Movistar con VAP - Grupo 1',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line',
                'requires_portability' => true, // <-- Se añade para claridad
                'requires_vap' => true,
                'source_operators' => ['Movistar'],
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

        // Descuento corregido para Altas Nuevas
        Discount::create([
            'name' => '20% Alta Nueva o Migración sin VAP - Grupo 1',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line',
                'requires_portability' => false, // <-- Se añade para que aplique a altas nuevas
                'requires_vap' => false,'excluded_operators' => ['Movistar','Vodafone'],
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);
        
 // --- DESCUENTOS PARA PAQUETES 'NEGOCIO Extra 1, 3, 5' ---
        Discount::create([
            'name' => '20% Portabilidad con VAP  - Grupo 1',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => true, 'excluded_operators' => [],
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

        Discount::create([
            'name' => '10% Portabilidad sin VAP  - Grupo 1',
            'percentage' => 10, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => false, 'excluded_operators' => [],
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);

 // Descuento corregido para Altas Nuevas
        Discount::create([
            'name' => '10% Alta Nueva o Migración sin VAP - Grupo 1',
            'percentage' => 10, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line',
                'requires_portability' => false, // <-- Se añade para que aplique a altas nuevas
                'requires_vap' => false,'excluded_operators' => ['Movistar','Vodafone'],
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5']
            ]
        ]);



















        
        // --- DESCUENTOS PARA PAQUETES 'NEGOCIO Extra 10, 20' ---
        Discount::create([
            'name' => '15% con VAP (No Movistar) - Grupo 2',
            'percentage' => 15, 'duration_months' => 24,
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

         // --- DESCUENTOS PARA GRUPO 1 Y 2 TV BARES (PRIORITARIOS) ---
        Discount::create([
            'name' => '50% Portabilidad con VAP (TV Bares)',
            'percentage' => 50, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => true, 'requires_tv_bares' => true,
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5', 'NEGOCIO Extra 10', 'NEGOCIO Extra 20']
            ]
        ]);

        Discount::create([
            'name' => '40% Alta Nueva sin VAP (TV Bares)',
            'percentage' => 40, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => false,
                'requires_vap' => false, 'requires_tv_bares' => true, 
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5', 'NEGOCIO Extra 10', 'NEGOCIO Extra 20']
            ]
        ]);

        Discount::create([
            'name' => '40% Portabilidad sin VAP (TV Bares)',
            'percentage' => 40, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => false, 'requires_tv_bares' => true,
                'package_names' => ['Base Plus','NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5', 'NEGOCIO Extra 10', 'NEGOCIO Extra 20']
            ]
        ]);
    }
}
