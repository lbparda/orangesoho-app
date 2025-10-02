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
        

        // 2. Buscamos los paquetes por nombre
        $group1 = Package::whereIn('name', ['Base Plus', 'NEGOCIO Extra 1'])->get()->pluck('id');
        $group2 = Package::whereIn('name', ['NEGOCIO Extra 3'])->get()->pluck('id');
        $group3 = Package::whereIn('name', ['NEGOCIO Extra 5'])->get()->pluck('id');
        $group4 = Package::whereIn('name', ['NEGOCIO Extra 10', 'NEGOCIO Extra 20'])->get()->pluck('id');
        // ... puedes añadir más grupos según tu Excel

       
        
        // Y así sucesivamente con el resto de terminales...
    }
}