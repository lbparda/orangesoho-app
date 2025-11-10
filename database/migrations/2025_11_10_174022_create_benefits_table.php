<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('description'); // El texto que ve el vendedor: "Fibra Oro (Gratis)", "Microsoft 365 (50% dto.)"
            $table->string('category'); // "Empresa" o "Hogar"

            // La 'regla' a aplicar
            $table->enum('apply_type', ['free', 'percentage_discount', 'fixed_discount']);
            $table->decimal('apply_value', 8, 2)->nullable(); // 100 (para free), 50 (para 50%), 5 (para 5€ dto)

            // Conecta la 'Regla' con el 'Producto'
            $table->foreignId('addon_id')->constrained('addons')->onDelete('cascade');

            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('benefits'); }
};