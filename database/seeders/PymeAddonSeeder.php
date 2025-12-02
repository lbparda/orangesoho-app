<?php

namespace Database\Seeders;

use App\Models\PymeAddon; // Importamos el nuevo modelo PymeAddon
use Illuminate\Database\Seeder;

class PymeAddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Funcionalidades de Internet Fijo
        PymeAddon::updateOrCreate(
            ['name' => 'IP Fija'],
            [
                'type' => 'internet_feature', 
                'price' => 12.00,
                'commission' => 0.00,
                'decommission' => 0.00, 
            ]
        );

        PymeAddon::updateOrCreate(
            ['name' => 'Fibra Oro'],
            [
                'type' => 'internet_feature', 
                'price' => 5.00,
                'commission' => 20.00,
                'decommission' => 10.00, 
            ]
        );

        // 2. Extensiones de Centralita
        PymeAddon::updateOrCreate(
            ['name' => 'Extensión Básica'],
            [
                'type' => 'centralita_extension', 
                'price' => 10.00, 
                'commission' => 25.00,
                'decommission' => 0.00,
            ]
        );

        PymeAddon::updateOrCreate(
            ['name' => 'Extensión Inalámbrica'],
            [
                'type' => 'centralita_extension', 
                'price' => 12.00, 
                'commission' => 25.00,
                'decommission' => 0.00,
            ]
        );

        PymeAddon::updateOrCreate(
            ['name' => 'Extensión Avanzada'],
            [
                'type' => 'centralita_extension', 
                'price' => 17.00, 
                'commission' => 45.00,
                'decommission' => 0.00,
            ]
        );
        
        // 3. Funcionalidades de Centralita
        PymeAddon::updateOrCreate(
            ['name' => 'Operadora Automática'],
            [
                'type' => 'centralita_feature',
                'price' => 10.00, 
                'commission' => 10.00, 
                'decommission' => 0.00,
                'description' => 'Servicio de operadora automática para la centralita.',
            ]
        );

        // 4. Funcionalidades de CENTRALITA OPLUS (NUEVO)
        PymeAddon::updateOrCreate(
            ['name' => 'MFO'],
            [
                'type' => 'centralita_mobile', 
                'price' => 5.00,
                'commission' => 10.00,
                'decommission' => 0.00, 
                'description' => 'Funcionalidad MFO para centralita móvil.',
            ]
        );

        PymeAddon::updateOrCreate(
            ['name' => 'Agente Centralita'],
            [
                'type' => 'centralita_mobile', 
                'price' => 5.00,
                'commission' => 10.00,
                'decommission' => 0.00, 
                'description' => 'Licencia de Agente para centralita móvil.',
            ]
        );
    }
}