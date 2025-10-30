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
            $table->boolean('has_ip_fija')->default(false)->after('quantity');
            $table->unsignedBigInteger('selected_centralita_id')->nullable()->after('has_ip_fija');

            // Opcional: si quieres una clave foránea real
            // $table->foreign('selected_centralita_id')->references('id')->on('addons')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addon_offer', function (Blueprint $table) {
            // $table->dropForeign(['selected_centralita_id']); // Descomenta si usaste la clave foránea
            $table->dropColumn(['has_ip_fija', 'selected_centralita_id']);
        });
    }
};
