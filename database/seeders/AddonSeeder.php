<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    public function run(): void
    {
        // --- LÍNEAS MÓVILES ---
        Addon::updateOrCreate(
            ['name' => 'Líneas adicionales móviles'],
            ['type' => 'mobile_line', 'description' => 'Servicio para añadir líneas móviles adicionales a un paquete.']
        );

        // --- FIBRA PRINCIPAL ---
        Addon::updateOrCreate(
            ['name' => 'Fibra 1Gb'],
            ['type' => 'internet', 'description' => 'Conexión de fibra óptica a 1Gbps.']
        );
        Addon::updateOrCreate(
            ['name' => 'Fibra 10Gb'],
            ['type' => 'internet', 'description' => 'Conexión de fibra óptica a 10Gbps.']
        );

        // --- FIBRA ADICIONAL ---
        Addon::updateOrCreate(
            ['name' => 'Fibra Adicional 600Mb'],
            ['type' => 'internet_additional', 'price' => 8.22, 'commission' => 60.00]
        );
        Addon::updateOrCreate(
            ['name' => 'Fibra Adicional 1Gb'],
            ['type' => 'internet_additional', 'price' => 15.00, 'commission' => 110.00]
        );

        // --- LÓGICA DE CENTRALITA - ESTRUCTURA CORRECTA ---

        // 1. LAS CENTRALITAS (Servicios Principales que se eligen)
        // El usuario elegirá UNA de estas para los paquetes 1, 3, 5.
        Addon::updateOrCreate(
            ['name' => 'Centralita Básica'],
            ['type' => 'centralita', 'price' => 5.00, 'commission' => 100.00, 'description' => 'Servicio principal de Centralita Básica.']
        );
        Addon::updateOrCreate(
            ['name' => 'Centralita Inalámbrica'],
            ['type' => 'centralita', 'price' => 7.00, 'commission' => 100.00, 'description' => 'Servicio principal de Centralita Inalámbrica.']
        );
        Addon::updateOrCreate(
            ['name' => 'Centralita Avanzada'],
            ['type' => 'centralita', 'price' => 14.00, 'commission' => 120.00, 'description' => 'Servicio principal de Centralita Avanzada.']
        );
        
        // LA CENTRALITA INCLUIDA (Para paquetes grandes)
        Addon::updateOrCreate(
            ['name' => 'Centralita Avanzada Incluida'],
            ['type' => 'centralita', 'price' => 0.00, 'commission' => 120.00, 'description' => 'Centralita Avanzada incluida en paquetes superiores.']
        );

        // 2. LAS EXTENSIONES (Puestos adicionales)
        // Se pueden añadir después de elegir una centralita.
        
        Addon::updateOrCreate(
            ['name' => 'Extensión Básica'],
            ['type' => 'centralita_extension', 'price' => 10.00, 'commission' => 25.00]
        );
        Addon::updateOrCreate(
            ['name' => 'Extensión Inalámbrica'],
            ['type' => 'centralita_extension', 'price' => 12.00, 'commission' => 25.00]
        );
        Addon::updateOrCreate(
            ['name' => 'Extensión Avanzada'],
            ['type' => 'centralita_extension', 'price' => 17.00, 'commission' => 45.00]
        );
         // --- NUEVO ADDON: OPERADORA AUTOMÁTICA ---
        Addon::updateOrCreate(
            ['name' => 'Operadora Automática'],
            [
                'type' => 'centralita_feature',
                'price' => 10.00, // Precio por defecto para el caso opcional
                'commission' => 10.00, // Comisión por defecto para el caso opcional
                'description' => 'Servicio de operadora automática para la centralita.'
            ]
        );
          // --- TV FUTBOL ---
        Addon::updateOrCreate(
            ['name' => 'Futbol'],
            ['type' => 'tv', 'price' => 29.00, 'commission' => 50.00, 'description' => 'Todo el fútbol.']
        );
        Addon::updateOrCreate(
            ['name' => 'Futbol y más deportes'],
            ['type' => 'tv', 'price' => 33.00, 'commission' => 55.00, 'description' => 'Fútbol y una selección de otros deportes.']
        );
        // --- NUEVO: ADDONS PARA TV BARES POR HABITANTES ---
        Addon::updateOrCreate(
            ['name' => 'Futbol Bares < 10.000 hab.'],
            ['type' => 'tv', 'price' => 285.00, 'commission' => 50.00, 'description' => 'TV para bares en localidades con menos de 10.000 habitantes.']
        );

        Addon::updateOrCreate(
            ['name' => 'Futbol Bares 10.000-45.000 hab.'],
            ['type' => 'tv', 'price' => 310.00, 'commission' => 50.00, 'description' => 'TV para bares en localidades entre 10.000 y 45.000 habitantes.']
        );

        Addon::updateOrCreate(
            ['name' => 'Futbol Bares 45.000-250.000 hab.'],
            ['type' => 'tv', 'price' => 330.00, 'commission' => 50.00, 'description' => 'TV para bares en localidades entre 45.000 y 250.000 habitantes.']
        );

        Addon::updateOrCreate(
            ['name' => 'Futbol Bares > 250.000 hab.'],
            ['type' => 'tv', 'price' => 355.00, 'commission' => 50.00, 'description' => 'TV para bares en localidades con más de 250.000 habitantes.']
        );

        Addon::create([
            'name' => 'IP Fija',
            'type' => 'internet_feature', 
            'price' => 12.00,
            'commission' => 0.00 ]
        );


    }
}