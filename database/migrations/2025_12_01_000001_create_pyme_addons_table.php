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
        Schema::create('pyme_addons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type'); // ej: 'internet_feature', 'centralita_extension'
            $table->text('description')->nullable();
            
            // Campos de valores
            $table->decimal('price', 8, 2)->default(0.00);
            $table->decimal('commission', 8, 2)->default(0.00);
            $table->decimal('decommission', 8, 2)->default(0.00); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pyme_addons');
    }
};