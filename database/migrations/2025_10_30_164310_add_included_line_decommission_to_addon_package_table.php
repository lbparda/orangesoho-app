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
    Schema::table('addon_package', function (Blueprint $table) {
        // Asegúrate de que el tipo de dato coincida (ej. decimal)
        $table->decimal('included_line_decommission', 8, 2)->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addon_package', function (Blueprint $table) {
            //
        });
    }
};
