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
            $table->id();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('terminal_id')->constrained()->onDelete('cascade');
            $table->integer('duration_months')->default(24);
            $table->decimal('initial_cost', 8, 2)->default(0); // UNIFICADO
            $table->decimal('monthly_cost', 8, 2)->default(0); // UNIFICADO
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