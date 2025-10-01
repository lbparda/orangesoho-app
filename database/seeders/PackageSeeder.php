<?php
namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        Package::create(['name' => 'Base Plus', 'base_price' => 52]);
        Package::create(['name' => 'NEGOCIO Extra 1', 'base_price' => 63]);
        Package::create(['name' => 'NEGOCIO Extra 3', 'base_price' => 91]);
        Package::create(['name' => 'NEGOCIO Extra 5', 'base_price' => 122]);
        Package::create(['name' => 'NEGOCIO Extra 10', 'base_price' => 238]);
        Package::create(['name' => 'NEGOCIO Extra 20', 'base_price' => 378]);
    }
}