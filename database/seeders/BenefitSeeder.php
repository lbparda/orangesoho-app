<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Addon;
use App\Models\Benefit;
use App\Models\Package;

class BenefitSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. BUSCAR LOS ADDONS (PRODUCTOS) QUE CREASTE ---
        // (Usamos firstOrFail para detenernos si falta un addon)
        $addon_fibra_oro = Addon::where('name', 'Fibra Oro')->firstOrFail();
        $addon_ms365standard = Addon::where('name', 'Microsoft 365 Empresa Standard')->firstOrFail();
        $addon_presencia = Addon::where('name', 'Presencia Digital')->firstOrFail();
        $addon_factura = Addon::where('name', 'Factura electrónica')->firstOrFail();
        // Asumo que "LA Extra" es tu addon "Líneas adicionales móviles"
        $addon_la_extra = Addon::where('name', 'Líneas adicionales móviles')->firstOrFail(); 
        $addon_disney = Addon::where('name', 'Disney+ con +90 canales')->firstOrFail();
        $addon_amazon = Addon::where('name', 'Amazon Prime con +90 canales')->firstOrFail();


        // --- 2. CREAR LAS "REGLAS DE BENEFICIO" ---
        $b_fibra_oro = Benefit::updateOrCreate(
            ['addon_id' => $addon_fibra_oro->id, 'apply_type' => 'free'],
            ['description' => 'Fibra Oro (Gratis)', 'category' => 'Empresa', 'apply_type' => 'free']
        );
        
        $b_la_extra = Benefit::updateOrCreate(
            ['addon_id' => $addon_la_extra->id, 'apply_type' => 'free'],
            ['description' => 'LA Extra (Gratis)', 'category' => 'Empresa', 'apply_type' => 'free']
        );

        
        $b_ms365standard = Benefit::updateOrCreate(
            ['addon_id' => $addon_ms365standard->id, 'apply_type' => 'percentage_discount'],
            ['description' => 'Microsoft 365 Empresa Standard (50% dto.)', 'category' => 'Empresa', 'apply_type' => 'percentage_discount', 'apply_value' => 50]
        );

        $b_presencia = Benefit::updateOrCreate(
            ['addon_id' => $addon_presencia->id, 'apply_type' => 'percentage_discount'],
            ['description' => 'Presencia Digital (50% dto.)', 'category' => 'Empresa', 'apply_type' => 'percentage_discount', 'apply_value' => 50]
        );

        $b_factura = Benefit::updateOrCreate(
            ['addon_id' => $addon_factura->id, 'apply_type' => 'percentage_discount'],
            ['description' => 'Factura electrónica (50% dto.)', 'category' => 'Empresa', 'apply_type' => 'percentage_discount', 'apply_value' => 50]
        );

        $b_disney = Benefit::updateOrCreate(
            ['addon_id' => $addon_disney->id, 'apply_type' => 'free'], // Asumo que Disney es Gratis, no solo informativo
            ['description' => 'Disney+ con +90 canales', 'category' => 'Hogar', 'apply_type' => 'free']
        );

        $b_amazon = Benefit::updateOrCreate(
            ['addon_id' => $addon_amazon->id, 'apply_type' => 'free'], // Asumo que Amazon es Gratis
            ['description' => 'Amazon Prime con +90 canales', 'category' => 'Hogar', 'apply_type' => 'free']
        );

        // --- 3. ASIGNAR BENEFICIOS Y LÍMITES A PAQUETES ---

        // Listas de beneficios
        $empresa_comun = [$b_fibra_oro->id, $b_ms365standard->id, $b_presencia->id, $b_factura->id];
        $hogar_comun = [$b_disney->id, $b_amazon->id];
        $empresa_10_20 = array_merge($empresa_comun, [$b_la_extra->id]);

        // Paquete 1
        $pkg1 = Package::where('name', 'like', 'NEGOCIO Extra 1')->first();
        if ($pkg1) {
            $pkg1->update(['benefit_limit' => 1]);
            $pkg1->benefits()->sync(array_merge($empresa_comun, $hogar_comun));
        }

        // Paquete 3
        $pkg3 = Package::where('name', 'like', 'NEGOCIO Extra 3')->first();
        if ($pkg3) {
            $pkg3->update(['benefit_limit' => 2]);
            $pkg3->benefits()->sync(array_merge($empresa_comun, $hogar_comun));
        }

        // Paquete 5
        $pkg5 = Package::where('name', 'like', 'NEGOCIO Extra 5')->first();
        if ($pkg5) {
            $pkg5->update(['benefit_limit' => 3]);
            $pkg5->benefits()->sync(array_merge($empresa_comun, $hogar_comun));
        }

        // Paquete 10
        $pkg10 = Package::where('name', 'like', 'NEGOCIO Extra 10')->first();
        if ($pkg10) {
            $pkg10->update(['benefit_limit' => 3]);
            $pkg10->benefits()->sync(array_merge($empresa_10_20, $hogar_comun));
        }

        // Paquete 20
        $pkg20 = Package::where('name', 'like', 'NEGOCIO Extra 20')->first();
        if ($pkg20) {
            $pkg20->update(['benefit_limit' => 3]);
            $pkg20->benefits()->sync(array_merge($empresa_10_20, $hogar_comun));
        }
    }
}