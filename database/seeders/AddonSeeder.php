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
            ['type' => 'internet_additional', 'price' => 13, 'commission' => 60.00]
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
            ['type' => 'centralita', 'price' => 5.00, 'commission' => 100.00,'decommission' => 25.00, 'description' => 'Servicio principal de Centralita Básica.']
        );
        Addon::updateOrCreate(
            ['name' => 'Centralita Inalámbrica'],
            ['type' => 'centralita', 'price' => 7.00, 'commission' => 100.00,'decommission' => 25.00, 'description' => 'Servicio principal de Centralita Inalámbrica.']
        );
        Addon::updateOrCreate(
            ['name' => 'Centralita Avanzada'],
            ['type' => 'centralita', 'price' => 14.00, 'commission' => 120.00,'decommission' => 25.00, 'description' => 'Servicio principal de Centralita Avanzada.']
        );
        
        // LA CENTRALITA INCLUIDA (Para paquetes grandes)
        Addon::updateOrCreate(
            ['name' => 'Centralita Avanzada Incluida'],
            ['type' => 'centralita', 'price' => 0.00, 'commission' => 100.00, 'decommission' => 25.00,'description' => 'Centralita Avanzada incluida en paquetes superiores.']
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
            ['type' => 'tv', 'price' => 31.00, 'commission' => 50.00, 'description' => 'Todo el fútbol.']
        );
        Addon::updateOrCreate(
            ['name' => 'Futbol y más deportes'],
            ['type' => 'tv', 'price' => 35.00, 'commission' => 55.00, 'description' => 'Fútbol y una selección de otros deportes.']
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
        
        Addon::updateOrCreate([
            'name' => 'IP Fija',
            'type' => 'internet_feature', 
            'price' => 12.00,
            'commission' => 0.00 ]
        );
        Addon::updateOrCreate([
                'name' => 'Fibra Oro',
                'type' => 'internet_feature', // Lo ponemos como "feature" igual que la IP Fija
                'price' => 5.00,
                'commission' => 20.00,
                'decommission' => 10.00,
                'created_at' => now(),
                'updated_at' => now() ]
            );
        Addon::updateOrCreate(
            ['name' => 'Microsoft 365 Empresa Basica'],
            [
                'type' => 'service', // O 'software', como prefieras
                'price' => 5.60, // Precio real (el beneficio dará 50% dto.)
                'commission' => 0.00, // Ajusta la comisión
                'decommission' => 0.00,
                'description' => 'Producto Microsoft 365',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        Addon::updateOrCreate(
            ['name' => 'Microsoft 365 Empresa Standard'],
            [
                'type' => 'service', // O 'software', como prefieras
                'price' => 11.70, // Precio real (el beneficio dará 50% dto.)
                'commission' => 40.00, // Ajusta la comisión
                'decommission' => 20.00,
                'description' => 'Producto Microsoft 365 (para beneficio)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // 3. Presencia Digital
        Addon::updateOrCreate(
            ['name' => 'Presencia Digital'],
            [
                'type' => 'service',
                'price' => 10.00, // Precio real (el beneficio dará 50% dto.)
                'commission' => 40.00, // Ajusta la comisión
                'decommission' => 20.00,
                'description' => 'Producto Presencia Digital (para beneficio)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // 4. Factura electrónica
        Addon::updateOrCreate(
            ['name' => 'Factura electrónica'],
            [
                'type' => 'service',
                'price' => 12.00, // ¡Placeholder! Ajusta el precio real
                'commission' => 40.00, // Ajusta la comisión
                'decommission' => 20.00,
                'description' => 'Producto Factura electrónica (para beneficio)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // 5. Disney+
        Addon::updateOrCreate(
            ['name' => 'Disney+ con +90 canales'],
            [
                'type' => 'tv',
                'price' => 4.95, // Precio real
                'commission' => 10.00, // Ajusta la comisión
                'decommission' => 5.00,
                'description' => 'Producto Disney+ (para beneficio)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // 6. Amazon Prime
        Addon::updateOrCreate(
            ['name' => 'Amazon Prime con +90 canales'],
            [
                'type' => 'tv', // O 'service'
                'price' => 4.12, // ¡Placeholder! Ajusta el precio real (PVP Amazon)
                'commission' => 10.00, // Ajusta la comisión
                'decommission' => 5.00,
                'description' => 'Producto Amazon Prime (para beneficio)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );    
        // 6. HBO
        Addon::updateOrCreate(
            ['name' => 'HBO MAX con +90 canales'],
            [
                'type' => 'tv', // O 'service'
                'price' => 5.78, // ¡Placeholder! Ajusta el precio real (PVP HBO)
                'commission' => 10.00, // Ajusta la comisión
                'decommission' => 0.00,
                'description' => 'Producto HBO MAX',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ); 
        // 7. Netflix
        Addon::updateOrCreate(
            ['name' => 'Netflix con +90 canales'],
            [
                'type' => 'tv', // O 'service'
                'price' => 6.99, // ¡Placeholder! Ajusta el precio real (PVP Netflix)
                'commission' => 10.00, // Ajusta la comisión
                'decommission' => 5.00,
                'description' => 'Producto Netflix (para beneficio)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );    

        // 8. Netflix
        Addon::updateOrCreate(
            ['name' => 'Dazn Motor con +90 canales'],
            [
                'type' => 'tv', // O 'service'
                'price' => 16.52, // ¡Placeholder! Ajusta el precio real (PVP dazn motor)
                'commission' => 10.00, // Ajusta la comisión
                'decommission' => 5.00,
                'description' => 'Producto Dazn Motor',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );    
        // 8. Netflix
        Addon::updateOrCreate(
            ['name' => 'Dazn Baloncesto con +90 canales'],
            [
                'type' => 'tv', // O 'service'
                'price' => 8.22, // ¡Placeholder! Ajusta el precio real (PVP dazn basquet)
                'commission' => 10.00, // Ajusta la comisión
                'decommission' => 5.00,
                'description' => 'Producto Dazn Baloncesto',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );    
        
        

        // --- NUEVO ADDON: DDI (Marcación Directa de Entrada) ---
        Addon::updateOrCreate( // <-- AÑADIDO
            ['name' => 'DDI'],
            [
                'type' => 'centralita_feature',
                'price' => 1.00, // Precio base (la lógica de cálculo lo ajustará a 0€ o 1€ según el paquete)
                'commission' =>0.00, 
                'decommission' => 0.00,
                'description' => 'Marcación Directa de Entrada para Centralita.'
            ]
        ); // <-- FIN AÑADIDO  


    }
}