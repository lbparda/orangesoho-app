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
        Schema::table('clients', function (Blueprint $table) {
            // Añadimos todas las columnas nuevas después de la columna 'cif_nif'
            $table->string('type')->after('cif_nif')->nullable();
            $table->string('first_name')->after('name')->nullable();
            $table->string('last_name')->after('first_name')->nullable();
            $table->string('street_number')->after('address')->nullable();
            $table->string('floor')->after('street_number')->nullable();
            $table->string('door')->after('floor')->nullable();
            $table->string('postal_code')->after('door')->nullable();
            $table->string('city')->after('postal_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Esto permite deshacer los cambios si es necesario
            $table->dropColumn([
                'type',
                'first_name',
                'last_name',
                'street_number',
                'floor',
                'door',
                'postal_code',
                'city',
            ]);
        });
    }
};