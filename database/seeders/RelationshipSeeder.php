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

        // 1. Creamos las relaciones de Addons (Líneas)
        if ($mobileAddon && $packages->isNotEmpty()) {
            $packages['Base Plus']->addons()->attach($mobileAddon->id, ['price' => 15.00, 'is_included' => true, 'included_quantity' => 1, 'line_limit' => 4]);
            $packages['NEGOCIO Extra 1']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 1, 'line_limit' => 4]);
            $packages['NEGOCIO Extra 3']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 3, 'line_limit' => 9]);
            $packages['NEGOCIO Extra 5']->addons()->attach($mobileAddon->id, ['price' => 13.00, 'is_included' => true, 'included_quantity' => 5, 'line_limit' => 9]);
        }
        
        // 2. Creamos las relaciones de O2O Discounts (LA LÓGICA QUE FALTABA)
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






// 3. Creamos las relaciones de Terminales
        if ($s24 && $packages->isNotEmpty()) {
            $group1_ids = Package::whereIn('name', ['NEGOCIO Extra 1', 'NEGOCIO Extra 3', 'NEGOCIO Extra 5'])->pluck('id');
            // 👇 LÍNEA CORREGIDA 👇
            $s24->packages()->attach($group1_ids, ['initial_cost' => 24.00, 'monthly_cost' => 25.00]);
        }
    }
}