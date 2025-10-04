<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            // Añadimos columnas para precio y comisión, que pueden ser nulas
            $table->decimal('price', 8, 2)->nullable()->after('description');
            $table->decimal('commission', 8, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            $table->dropColumn(['price', 'commission']);
        });
    }
};