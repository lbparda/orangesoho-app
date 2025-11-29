<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pyme_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "EMPRESAS VOZ"
            $table->decimal('base_price', 10, 2)->default(0);
            
            // Columnas de Comisiones
            $table->decimal('commission_optima', 10, 2)->default(0);
            $table->decimal('commission_custom', 10, 2)->default(0);
            $table->decimal('commission_porta', 10, 2)->default(30.00);
            
            // Bonus Permanencia
            $table->decimal('bonus_cp_24', 10, 2)->default(0);
            $table->decimal('bonus_cp_36', 10, 2)->default(0);
            $table->decimal('bonus_cp_24_terminal', 10, 2)->default(0);
            $table->decimal('bonus_cp_36_terminal', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pyme_packages');
    }
};