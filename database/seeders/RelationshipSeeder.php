<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\O2oDiscount;
use App\Models\Package;
use Illuminate\Database\Seeder;

class RelationshipSeeder extends Seeder
{
    public function run(): void
    {
        // 1. --- BUSCAR TODOS LOS MODELOS NECESARIOS ---
        $packages = Package::all()->keyBy('name');
        if ($packages->isEmpty()) {
            return;
        }

        $mobileAddon = Addon::where('name', 'Líneas adicionales móviles')->first();
        $internet1Gb = Addon::where('name', 'Fibra 1Gb')->first();
        $internet10Gb = Addon::where('name', 'Fibra 10Gb')->first();
        $o2o_discounts = O2oDiscount::all()->keyBy('total_discount_amount');

        // Buscamos los addons de centralita
        $centralitaBasica = Addon::where('name', 'Centralita Básica')->first();
        $centralitaInalambrica = Addon::where('name', 'Centralita Inalámbrica')->first();
        $centralitaAvanzada = Addon::where('name', 'Centralita Avanzada')->first();
        $centralitaAvanzadaIncluida = Addon::where('name', 'Centralita Avanzada Incluida')->first();
        $extensionAvanzada = Addon::where('name', 'Extensión Avanzada')->first();
        $operadoraAutomatica = Addon::where('name', 'Operadora Automática')->first();

        // --- 2. RELACIONES DE LÍNEAS MÓVILES ---
        if ($mobileAddon) {
            $packages['Base Plus']->addons()->attach($mobileAddon->id, ['price' => 15.00, 'is_included' => true, 'included_quantity' => 1, 'line_limit' => 4,'included_line_commission' => 50.00, 'additional_line_commission' => 50.00]);
            $packages['NEGOCIO Extra 1']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 1, 'line_limit' => 4,'included_line_commission' => 70.00, 'additional_line_commission' => 70.00]);
            $packages['NEGOCIO Extra 3']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 3, 'line_limit' => 4,'included_line_commission' => 85.00, 'additional_line_commission' => 85.00]);
            $packages['NEGOCIO Extra 5']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 5, 'line_limit' => 4,'included_line_commission' => 95.00, 'additional_line_commission' => 95.00]);
            $packages['NEGOCIO Extra 10']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 10, 'line_limit' => 9,'included_line_commission' => 170.00, 'additional_line_commission' => 170.00]);
            $packages['NEGOCIO Extra 20']->addons()->attach($mobileAddon->id, ['price' => 11.00, 'is_included' => true, 'included_quantity' => 20, 'line_limit' => 20,'included_line_commission' => 170.00, 'additional_line_commission' => 170.00]);
        }

        // --- 3. RELACIONES DE FIBRA PRINCIPAL ---
        if ($internet1Gb && $internet10Gb) {
            foreach ($packages as $package) {
                $package->addons()->attach($internet1Gb->id, ['price' => 0.00, 'is_included' => true, 'included_line_commission' => 160]);
                $package->addons()->attach($internet10Gb->id, ['price' => 8.26, 'is_included' => true, 'included_line_commission' => 200.00]);
            }
        }

        // --- 4. RELACIONES DE CENTRALITA (CORREGIDO) ---
        // PAQUETES 1, 3, 5 (se asocian las 3 centralitas como opcionales)
        $paquetesOpcionales = ['NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5'];
        $centralitasOpcionales = [$centralitaBasica, $centralitaInalambrica, $centralitaAvanzada];

        foreach ($paquetesOpcionales as $nombrePaquete) {
            if (isset($packages[$nombrePaquete])) {
                foreach($centralitasOpcionales as $centralita) {
                    if ($centralita) {
                        $packages[$nombrePaquete]->addons()->attach($centralita->id, [
                            'is_included' => false,
                            'price' => $centralita->price // El precio de la relación es el precio del addon
                        ]);
                    }
                }
                // Asocia la Operadora Automática como opcional
                if ($operadoraAutomatica) {
                    $packages[$nombrePaquete]->addons()->attach($operadoraAutomatica->id, [
                        'is_included' => false,
                        'price' => 10.00, // Precio específico para la relación
                        'included_line_commission' => 10.00 // Usamos este campo aunque no esté incluida
                    ]);
                }
            }
        }

        // PAQUETES 10, 20
        $paquetesGrandes = ['NEGOCIO Extra 10', 'NEGOCIO Extra 20'];
        foreach($paquetesGrandes as $nombrePaquete) {
            if (isset($packages[$nombrePaquete])) {
                // Incluir Centralita Avanzada
                if ($centralitaAvanzadaIncluida) {
                     $packages[$nombrePaquete]->addons()->attach($centralitaAvanzadaIncluida->id, ['is_included' => true, 'price' => 0, 'included_line_commission' => 120]);
                }
                // Incluir Operadora Automática
                if ($operadoraAutomatica) {
                    $packages[$nombrePaquete]->addons()->attach($operadoraAutomatica->id, [
                        'is_included' => true,
                        'price' => 0.00,
                        'included_line_commission' => 10.00
                    ]);
                }
                // Incluir Extensiones Gratis
                if ($extensionAvanzada) {
                    $qty = ($nombrePaquete === 'NEGOCIO Extra 10') ? 1 : 2;
                    $packages[$nombrePaquete]->addons()->attach($extensionAvanzada->id, ['is_included' => true, 'included_quantity' => $qty, 'price' => 0, 'included_line_commission' => 45]);
                }
            }
        }
        // --- 5. RELACIONES DE O2O DISCOUNTS ---
        if ($o2o_discounts->isNotEmpty()) {
            if (isset($o2o_discounts['24.00'])) {
                $o2o_id = $o2o_discounts['24.00']->id;
                $packages['NEGOCIO Extra 1']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                $packages['NEGOCIO Extra 3']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                $packages['NEGOCIO Extra 5']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 80, 'dho_payment' => 4.8, 'osp_payment' => 19.2]);
                $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 80, 'dho_payment' => 4.8, 'osp_payment' => 19.2]);
            }
            if (isset($o2o_discounts['72.00'])) {
                $o2o_id = $o2o_discounts['72.00']->id;
                $packages['NEGOCIO Extra 1']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 21.6, 'osp_payment' => 50.4]);
                $packages['NEGOCIO Extra 3']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                $packages['NEGOCIO Extra 5']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
            }
            if (isset($o2o_discounts['120.00'])) {
                $o2o_id = $o2o_discounts['120.00']->id;
                $packages['NEGOCIO Extra 5']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 30, 'dho_payment' => 84, 'osp_payment' =>36]);
                $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
            }
            if (isset($o2o_discounts['144.00'])) {
                $o2o_id = $o2o_discounts['144.00']->id;
                $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
            }
            if (isset($o2o_discounts['168.00'])) {
                $o2o_id = $o2o_discounts['168.00']->id;
                $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
            }
            if (isset($o2o_discounts['192.00'])) {
                $o2o_id = $o2o_discounts['192.00']->id;
                $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
            }
        }
    }
}