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
        Schema::create('pyme_o2o_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "O2O 10%"
            $table->integer('percentage'); // Ej: 10
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pyme_o2o_discounts');
    }
};