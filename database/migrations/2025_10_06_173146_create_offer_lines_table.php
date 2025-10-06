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
        Schema::create('offer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->boolean('is_extra')->default(false);
            $table->boolean('is_portability')->default(false);
            $table->string('phone_number')->nullable();
            $table->string('source_operator')->nullable();
            $table->boolean('has_vap')->default(false);
            $table->foreignId('o2o_discount_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('package_terminal_id')->nullable(); // ID de la tabla pivote package_terminal
            $table->decimal('initial_cost', 8, 2)->default(0);
            $table->decimal('monthly_cost', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_lines');
    }
};

