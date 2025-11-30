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
            PymePackageSeeder::class,
            AddonSeeder::class,
            PymeAddonSeeder::class,
            DiscountSeeder::class,
            O2oDiscountSeeder::class,
            PymeO2oDiscountSeeder::class,
            TerminalSeeder::class, // Seeder de terminales de prueba
            NachoUserSeeder::class,
            BenefitSeeder::class, // <-- 3. FINALMENTE, crea las reglas y las enlaza
        ]);

        // 2. Al final, ejecutamos un seeder que se dedica SOLO a crear las relaciones.
        $this->call(RelationshipSeeder::class);
    }
}