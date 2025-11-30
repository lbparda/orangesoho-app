<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se añade la columna 'type' con un valor predeterminado 'movil' después de 'name'
        Schema::table('pyme_packages', function (Blueprint $table) {
            $table->string('type')->after('name')->default('movil'); 
        });
    }

    public function down(): void
    {
        // En caso de revertir la migración, se elimina la columna
        Schema::table('pyme_packages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};