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
            // --- INICIO CAMPOS SNAPSHOT ---
            // Guardamos los datos clave del Addon
            $table->string('addon_name')->nullable()->after('addon_id');
            $table->decimal('addon_price', 8, 2)->nullable()->after('addon_name');
            $table->decimal('addon_commission', 8, 2)->nullable()->after('addon_price');
            // --- FIN CAMPOS SNAPSHOT ---
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addon_offer', function (Blueprint $table) {
            $table->dropColumn(['addon_name', 'addon_price', 'addon_commission']);
        });
    }
};