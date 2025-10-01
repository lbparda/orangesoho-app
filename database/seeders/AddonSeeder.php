<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    public function run(): void
    {
        Addon::create([
            'name' => 'Líneas adicionales móviles',
            'type' => 'mobile_line',
            'description' => 'Servicio para añadir líneas móviles adicionales a un paquete.'
        ]);
    }
}