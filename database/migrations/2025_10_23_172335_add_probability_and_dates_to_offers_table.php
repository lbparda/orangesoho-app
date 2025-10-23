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
        Schema::table('offers', function (Blueprint $table) {
            // Añade los nuevos campos después de 'client_id' o donde prefieras
            $table->integer('probability')->nullable()->after('client_id'); // Puedes usar enum si prefieres valores fijos
            $table->date('signing_date')->nullable()->after('probability');
            $table->date('processing_date')->nullable()->after('signing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['probability', 'signing_date', 'processing_date']);
        });
    }
};