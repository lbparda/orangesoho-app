<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('package_terminal', function (Blueprint $table) {
            // Añadimos los dos descuentos, uno después de cada coste
            $table->decimal('initial_cost_discount', 8, 2)->nullable()->default(0)->after('monthly_cost');
            $table->decimal('monthly_cost_discount', 8, 2)->nullable()->default(0)->after('initial_cost_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_terminal', function (Blueprint $table) {
            $table->dropColumn('initial_cost_discount');
            $table->dropColumn('monthly_cost_discount');
        });
    }
};