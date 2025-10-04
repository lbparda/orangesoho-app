<?php

namespace Database\Seeders;

use App\Models\Addon;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    public function run(): void
    {
        // Este es el addon que ya tenías
        Addon::create([
            'name' => 'Líneas adicionales móviles',
            'type' => 'mobile_line',
            'description' => 'Servicio para añadir líneas móviles adicionales a un paquete.'
        ]);

        // NUEVO: Añadimos los addons para las velocidades de internet
        Addon::create([
            'name' => 'Fibra 1Gb',
            'type' => 'internet',
            'description' => 'Conexión de fibra óptica a 1Gbps.'
        ]);

        Addon::create([
            'name' => 'Fibra 10Gb',
            'type' => 'internet',
            'description' => 'Conexión de fibra óptica a 10Gbps.'
        ]);
            Addon::create([
        'name' => 'Fibra Adicional 600Mb',
        'type' => 'internet_additional',
        'price' => 8.22,
        'commission' => 15.00, // Comisión de ejemplo
        'description' => 'Línea de fibra adicional de 600Mbps.'
        ]);

        Addon::create([
            'name' => 'Fibra Adicional 1Gb',
            'type' => 'internet_additional',
            'price' => 15.00,
            'commission' => 30.00, // Comisión de ejemplo
            'description' => 'Línea de fibra adicional de 1Gbps.'
        ]);
        // NUEVO: Añadimos el addon para la Centralita
        Addon::create([
            'name' => 'Centralita Virtual',
            'type' => 'centralita',
            'description' => 'Servicio de centralita virtual para empresas.'
        ]);
    }
}