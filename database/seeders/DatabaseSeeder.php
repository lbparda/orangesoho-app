<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Primero, creamos TODAS las entidades base (los "ingredientes").
        $this->call([
            PackageSeeder::class,
            AddonSeeder::class,
            DiscountSeeder::class,
            O2oDiscountSeeder::class,
            TerminalSeeder::class, // Seeder de terminales de prueba
            NachoUserSeeder::class,
        ]);

        // 2. Al final, ejecutamos un seeder que se dedica SOLO a crear las relaciones.
        $this->call(RelationshipSeeder::class);
    }
}