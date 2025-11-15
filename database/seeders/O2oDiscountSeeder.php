<?php

namespace Database\Seeders;

use App\Models\O2oDiscount;
use Illuminate\Database\Seeder;

class O2oDiscountSeeder extends Seeder
{
    public function run(): void
    {
        O2oDiscount::create(['name' => '1€ x 24m', 'total_discount_amount' => 24, 'duration_months' => 24, 'commission_cost' => 0]);
        O2oDiscount::create(['name' => '3€ x 24m', 'total_discount_amount' => 72, 'duration_months' => 24, 'commission_cost' => 0]);
        O2oDiscount::create(['name' => '5€ x 24m', 'total_discount_amount' => 120, 'duration_months' => 24, 'commission_cost' => 0]);
        O2oDiscount::create(['name' => '6€ x 24m', 'total_discount_amount' => 144, 'duration_months' => 24, 'commission_cost' => 0]);
        O2oDiscount::create(['name' => '7€ x 24m', 'total_discount_amount' => 168, 'duration_months' => 24, 'commission_cost' => 0]);
        O2oDiscount::create(['name' => '8€ x 24m', 'total_discount_amount' => 192, 'duration_months' => 24, 'commission_cost' => 0]);
    }
}