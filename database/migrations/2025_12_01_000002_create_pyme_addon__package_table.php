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
        Schema::create('pyme_addon_package', function (Blueprint $table) {
            
            // Claves foráneas a las tablas PYME
            $table->foreignId('pyme_addon_id')->constrained('pyme_addons')->cascadeOnDelete();
            $table->foreignId('pyme_package_id')->constrained('pyme_packages')->cascadeOnDelete();

            // Clave primaria compuesta
            $table->primary(['pyme_addon_id', 'pyme_package_id']);
            
            // Campos de la tabla pivot
            $table->decimal('price', 8, 2)->default(0.00);
            $table->boolean('is_included')->default(false);
            $table->unsignedInteger('included_quantity')->default(0);
            $table->unsignedInteger('line_limit')->default(0);
            $table->decimal('included_line_commission', 8, 2)->default(0.00);
            $table->decimal('additional_line_commission', 8, 2)->default(0.00);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pyme_addon_package');
    }
};