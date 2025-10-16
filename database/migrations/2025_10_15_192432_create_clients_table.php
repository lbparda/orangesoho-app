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
    Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Nombre de la empresa o particular
        $table->string('cif_nif')->unique(); // CIF o NIF, debe ser único
        $table->string('contact_person')->nullable(); // Persona de contacto
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->text('address')->nullable(); // Dirección
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
