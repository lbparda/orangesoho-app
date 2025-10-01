<?php

namespace Database\Seeders;

use App\Models\Discount;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        Discount::create([
            'name' => '30% Portabilidad con VAP (No Movistar)',
            'percentage' => 30, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => true, 'excluded_operators' => ['Movistar']
            ]
        ]);

        Discount::create([
            'name' => '20% Portabilidad sin VAP (No Movistar)',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_portability' => true,
                'requires_vap' => false, 'excluded_operators' => ['Movistar']
            ]
        ]);

        Discount::create([
            'name' => '20% Portabilidad Movistar con VAP o Alta Nueva',
            'percentage' => 20, 'duration_months' => 24,
            'conditions' => [
                'applies_to' => 'principal_line', 'requires_vap' => true,
                'source_operators' => ['Movistar', 'new_customer', 'migration']
            ]
        ]);
    }
}