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
        Schema::table('offers', function (Blueprint $table) {
            // --- INICIO CAMPOS SNAPSHOT ---
            // Guardamos los datos del paquete en el momento de la creación
            $table->string('package_name')->nullable()->after('package_id');
            $table->decimal('package_price', 10, 2)->nullable()->after('package_name');
            $table->decimal('package_commission', 10, 2)->nullable()->after('package_price');
            // --- FIN CAMPOS SNAPSHOT ---

            // --- INICIO CAMPO DE BLOQUEO ---
            // 'borrador' (draft) = se puede editar
            // 'finalizada' (locked) = no se puede editar
            $table->string('status', 50)->default('borrador')->after('summary');
            // --- FIN CAMPO DE BLOQUEO ---
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['package_name', 'package_price', 'package_commission', 'status']);
        });
    }
};