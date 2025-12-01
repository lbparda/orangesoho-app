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
        // 1. Tabla de Terminales PYME (Independiente de SOHO)
        Schema::create('pyme_terminals', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->timestamps();
            
            // Índice único para evitar duplicados de marca/modelo
            $table->unique(['brand', 'model']);
        });

        // 2. Tabla Pivote para VAP (Venta a Plazos)
        // Relaciona PymePackage con PymeTerminal con precios VAP
        Schema::create('pyme_package_terminal_vap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pyme_package_id')->constrained('pyme_packages')->onDelete('cascade');
            $table->foreignId('pyme_terminal_id')->constrained('pyme_terminals')->onDelete('cascade');
            
            $table->integer('duration_months'); // Ej: 24, 36
            $table->decimal('initial_cost', 10, 2)->default(0); // Precio 1
            $table->decimal('monthly_cost', 10, 2)->default(0); // Precio 2
            
            $table->timestamps();
            
            // Evitar duplicados para el mismo paquete, terminal y duración
            $table->unique(['pyme_package_id', 'pyme_terminal_id', 'duration_months'], 'vap_unique_index');
        });

        // 3. Tabla Pivote para SUBVENCIONADOS
        // Relaciona PymePackage con PymeTerminal con precios de Subvención
        Schema::create('pyme_package_terminal_sub', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pyme_package_id')->constrained('pyme_packages')->onDelete('cascade');
            $table->foreignId('pyme_terminal_id')->constrained('pyme_terminals')->onDelete('cascade');
            
            $table->integer('duration_months'); // Ej: 24
            $table->decimal('cession_price', 10, 2)->default(0); // Precio 1 (Cesión)
            $table->decimal('subsidy_price', 10, 2)->default(0); // Precio 2 (Subvención)
            
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['pyme_package_id', 'pyme_terminal_id', 'duration_months'], 'sub_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pyme_package_terminal_sub');
        Schema::dropIfExists('pyme_package_terminal_vap');
        Schema::dropIfExists('pyme_terminals');
    }
};