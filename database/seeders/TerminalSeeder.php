<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Terminal;
use Illuminate\Database\Seeder;

class TerminalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creamos los terminales solo con su marca y modelo
        $s24 = Terminal::create(['brand' => 'SAMSUNG', 'model' => 'GALAXY S24']);
        $iphone15 = Terminal::create(['brand' => 'APPLE', 'model' => 'IPHONE 15']);
        $s24plus = Terminal::create(['brand' => 'SAMSUNG', 'model' => 'GALAXY S24+']);
        $zflip5 = Terminal::create(['brand' => 'SAMSUNG', 'model' => 'GALAXY Z FLIP5']);

        // 2. Buscamos los paquetes por nombre
        $group1 = Package::whereIn('name', ['Base Plus', 'NEGOCIO Extra 1'])->get()->pluck('id');
        $group2 = Package::whereIn('name', ['NEGOCIO Extra 3', 'NEGOCIO Extra 5'])->get()->pluck('id');
        // ... puedes añadir más grupos según tu Excel

        // 3. Asignamos los precios en la tabla intermedia
        // Para el GALAXY S24
        if ($s24) {
            $s24->packages()->attach($group1, ['initial_payment' => 20.00, 'monthly_fee' => 25.00]); // Precios para el primer grupo
            $s24->packages()->attach($group2, ['initial_payment' => 10.00, 'monthly_fee' => 22.00]); // Precios para el segundo grupo
        }

        // Para el IPHONE 15
        if ($iphone15) {
            $iphone15->packages()->attach($group1, ['initial_payment' => 30.00, 'monthly_fee' => 35.00]);
            $iphone15->packages()->attach($group2, ['initial_payment' => 20.00, 'monthly_fee' => 32.00]);
        }
        
        // Y así sucesivamente con el resto de terminales...
    }
}