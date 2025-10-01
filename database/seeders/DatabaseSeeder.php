<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Primero, creamos todos los "ingredientes" base.
        $this->call([
            PackageSeeder::class,
            AddonSeeder::class,
            DiscountSeeder::class,
            O2oDiscountSeeder::class,
            TerminalSeeder::class,
        ]);

        // 2. Una vez que todo existe, creamos las relaciones complejas.
        // Para ello, llamaremos a un nuevo seeder de relaciones.
        $this->call(RelationshipSeeder::class);
    }
}