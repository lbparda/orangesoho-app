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
        Schema::create('package_terminal', function (Blueprint $table) {
            // LA LÍNEA MÁS IMPORTANTE: Añade un ID único a cada registro.
            $table->id();

            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('terminal_id')->constrained()->onDelete('cascade');
            $table->integer('duration_months');
            $table->decimal('initial_cost', 8, 2)->default(0);
            $table->decimal('monthly_cost', 8, 2)->default(0);
            
            // Tus otras columnas de comisiones si las tienes
            $table->decimal('commission_dho', 8, 2)->default(0)->nullable();
            $table->decimal('commission_osp', 8, 2)->default(0)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_terminal');
    }
};
