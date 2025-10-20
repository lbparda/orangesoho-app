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
            ['type' => 'internet_additional', 'price' => 8.22, 'commission' => 15.00]
        );
        Addon::updateOrCreate(
            ['name' => 'Fibra Adicional 1Gb'],
            ['type' => 'internet_additional', 'price' => 15.00, 'commission' => 30.00]
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
          // --- TV FUTBOL ---
        Addon::updateOrCreate(
            ['name' => 'Futbol Bares <10000'],
            ['type' => 'tv', 'price' => 285.00, 'commission' => 50.00, 'description' => 'Todo el fútbol.']
        );
        Addon::updateOrCreate(
            ['name' => 'Futbol Bares <45000'],
            ['type' => 'tv', 'price' => 300.00, 'commission' => 55.00, 'description' => 'Todo el fútbol.']
        );


    }
}