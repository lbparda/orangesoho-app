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
        Schema::table('addon_offer', function (Blueprint $table) {
            // Añadimos esta columna, igual que 'has_ip_fija'
            $table->boolean('has_fibra_oro')->default(false)->after('has_ip_fija');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addon_offer', function (Blueprint $table) {
            $table->dropColumn('has_fibra_oro');
        });
    }
};