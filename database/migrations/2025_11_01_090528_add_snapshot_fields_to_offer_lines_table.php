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
        Schema::table('offer_lines', function (Blueprint $table) {
            // --- INICIO CAMPOS SNAPSHOT ---
            // Guardamos los datos del descuento O2O
            $table->string('o2o_discount_name')->nullable()->after('o2o_discount_id');
            $table->decimal('o2o_discount_amount', 8, 2)->nullable()->after('o2o_discount_name');

            // Guardamos los datos del terminal (si lo hay)
            $table->string('terminal_name')->nullable()->after('package_terminal_id');
            // Nota: 'initial_cost' y 'monthly_cost' ya existen en tu migración original,
            // así que las usaremos para guardar el coste final de la línea (con terminal).
            // Si quisieras guardar el coste *base* del terminal, añadirías más columnas.
            // --- FIN CAMPOS SNAPSHOT ---
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offer_lines', function (Blueprint $table) {
            $table->dropColumn(['o2o_discount_name', 'o2o_discount_amount', 'terminal_name']);
        });
    }
};