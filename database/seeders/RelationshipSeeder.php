<?php
namespace Database\Seeders;

use App\Models\Addon;
use App\Models\O2oDiscount;
use App\Models\Package;
use App\Models\Terminal;
use Illuminate\Database\Seeder;

class RelationshipSeeder extends Seeder
{
    public function run(): void
    {
        // Buscamos todos los modelos que ya existen
        $packages = Package::all()->keyBy('name');
        $mobileAddon = Addon::where('type', 'mobile_line')->first();
        $o2o_discounts = O2oDiscount::all()->keyBy('total_discount_amount');
        $s24 = Terminal::where('model', 'GALAXY S24')->first();

        // ✅ LÍNEAS QUE FALTABAN ✅
        // Buscamos los addons de internet que creamos en AddonSeeder
        $internetAddon1Gb = Addon::where('name', 'Fibra 1Gb')->first();
        $internetAddon10Gb = Addon::where('name', 'Fibra 10Gb')->first();
        // NUEVO: Buscamos el addon de Centralita
        $centralitaAddon = Addon::where('type', 'centralita')->first();
        $advancedExtension = Addon::where('name', 'Extensión Avanzada')->first();
        // 1. Creamos las relaciones de Addons (Líneas)
        if ($mobileAddon && $packages->isNotEmpty()) {
            $packages['Base Plus']->addons()->attach($mobileAddon->id, ['price' => 15.00, 'is_included' => true, 'included_quantity' => 1, 'line_limit' => 4,'included_line_commission' => 50.00, 'additional_line_commission' => 50.00]);
            $packages['NEGOCIO Extra 1']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 1, 'line_limit' => 4,'included_line_commission' => 70.00, 'additional_line_commission' => 70.00]);
            $packages['NEGOCIO Extra 3']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 3, 'line_limit' => 4,'included_line_commission' => 85.00, 'additional_line_commission' => 85.00]);
            $packages['NEGOCIO Extra 5']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 5, 'line_limit' => 4,'included_line_commission' => 95.00, 'additional_line_commission' => 95.00]);
            $packages['NEGOCIO Extra 10']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 10, 'line_limit' => 9,'included_line_commission' => 170.00, 'additional_line_commission' => 170.00]);
            $packages['NEGOCIO Extra 20']->addons()->attach($mobileAddon->id, ['price' => 11.00, 'is_included' => true, 'included_quantity' => 20, 'line_limit' => 20,'included_line_commission' => 170.00, 'additional_line_commission' => 170.00]);
        }
        
        // 2. Creamos las relaciones de O2O Discounts
        if ($packages->isNotEmpty() && $o2o_discounts->isNotEmpty()) {
            /// 2. Creamos las relaciones de O2O Discounts (LA LÓGICA QUE FALTABA)
                if (isset($o2o_discounts['24.00']) && $packages->isNotEmpty()) {
                    $o2o_id = $o2o_discounts['24.00']->id;
                    $packages['NEGOCIO Extra 1']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                    $packages['NEGOCIO Extra 3']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                    $packages['NEGOCIO Extra 5']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                    $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 80, 'dho_payment' => 4.8, 'osp_payment' => 19.2]);
                    $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 80, 'dho_payment' => 4.8, 'osp_payment' => 19.2]);
                }
                if (isset($o2o_discounts['72.00']) && $packages->isNotEmpty()) {
                    $o2o_id = $o2o_discounts['72.00']->id;
                    $packages['NEGOCIO Extra 1']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 21.6, 'osp_payment' => 50.4]);
                    $packages['NEGOCIO Extra 3']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                    $packages['NEGOCIO Extra 5']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                    $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                    $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 7.2, 'osp_payment' => 16.8]);
                }
                if (isset($o2o_discounts['120.00']) && $packages->isNotEmpty()) {
                    $o2o_id = $o2o_discounts['120.00']->id;
                    $packages['NEGOCIO Extra 5']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 30, 'dho_payment' => 84, 'osp_payment' =>36]);
                    $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                    $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                }
                if (isset($o2o_discounts['144.00']) && $packages->isNotEmpty()) {
                    $o2o_id = $o2o_discounts['144.00']->id;
                    $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                    $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                }
                if (isset($o2o_discounts['168.00']) && $packages->isNotEmpty()) {
                    $o2o_id = $o2o_discounts['168.00']->id;
                    $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                    $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                }
                if (isset($o2o_discounts['192.00']) && $packages->isNotEmpty()) {
                    $o2o_id = $o2o_discounts['192.00']->id;
                    $packages['NEGOCIO Extra 10']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                    $packages['NEGOCIO Extra 20']->o2oDiscounts()->attach($o2o_id, ['subsidy_percentage' => 70, 'dho_payment' => 36, 'osp_payment' => 84]);
                }
        }

        // 3. Creamos las relaciones de Terminales
        if ($packages->isNotEmpty()) {
              $group1_ids = Package::whereIn('name', ['NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5'])->pluck('id');
               
            }
        
        // 4. Creamos las relaciones de Addons de Internet
        if ($internetAddon1Gb && $internetAddon10Gb && $packages->isNotEmpty()) {
            foreach ($packages as $package) {
                // Asociar Fibra 1Gb a este paquete
                $package->addons()->attach($internetAddon1Gb->id, [
                    'price' => 0.00, // Sin coste adicional
                    'is_included' => true,
                    'included_quantity' => 1,
                    'included_line_commission' => 160,
                    
                ]);

                // Asociar Fibra 10Gb a este paquete
                $package->addons()->attach($internetAddon10Gb->id, [
                    'price' => 8.26, // Con coste adicional
                    'is_included' => true,
                    'included_quantity' => 1,
                    'included_line_commission' => 200.00,
                    
                ]);
            }
        }
        if ($centralitaAddon && $packages->isNotEmpty()) {
            
            // PAQUETES DONDE ES UN EXTRA (OPCIONAL)
            $paquetesOpcionales = ['NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5'];
            foreach ($paquetesOpcionales as $nombrePaquete) {
                if (isset($packages[$nombrePaquete])) {
                    $packages[$nombrePaquete]->addons()->attach($centralitaAddon->id, [
                        'price' => 5.00, // Precio si se contrata como extra
                        'is_included' => false,
                        'included_line_commission' => 25.00, // Comisión si se vende como extra
                    ]);
                }
            }

            // PAQUETES DONDE VIENE INCLUIDA
            $paquetesIncluidos = ['NEGOCIO Extra 10', 'NEGOCIO Extra 20'];
            foreach ($paquetesIncluidos as $nombrePaquete) {
                if (isset($packages[$nombrePaquete])) {
                    $packages[$nombrePaquete]->addons()->attach($centralitaAddon->id, [
                        'price' => 0.00, // Sin coste, va incluida
                        'is_included' => true,
                        'included_line_commission' => 35.00, // Comisión por defecto al ir incluida
                    ]);
                }
            }
        }
         // NUEVO: 5. Relaciones para Extensiones INCLUIDAS
        if ($advancedExtension && $packages->isNotEmpty()) {
            // NEGOCIO Extra 10 incluye 1 extensión avanzada
            if (isset($packages['NEGOCIO Extra 10'])) {
                $packages['NEGOCIO Extra 10']->addons()->attach($advancedExtension->id, [
                    'price' => 0.00, // Es gratis
                    'is_included' => true,
                    'included_quantity' => 1,
                    'included_line_commission' => 8.00, // Su comisión correspondiente
                ]);
            }
            // NEGOCIO Extra 20 incluye 2 extensiones avanzadas
            if (isset($packages['NEGOCIO Extra 20'])) {
                $packages['NEGOCIO Extra 20']->addons()->attach($advancedExtension->id, [
                    'price' => 0.00, // Es gratis
                    'is_included' => true,
                    'included_quantity' => 2,
                    'included_line_commission' => 8.00, // La comisión por unidad
                ]);
            }
        }
    }
}