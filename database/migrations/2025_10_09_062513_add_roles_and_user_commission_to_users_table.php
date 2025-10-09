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
        Schema::table('users', function (Blueprint $table) {
            // Define el rol del usuario: 'admin', 'team_lead', 'user'
            $table->string('role')->default('user')->after('is_admin');
            
            // Porcentaje que el jefe de equipo asigna a este usuario
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'commission_percentage']);
        });
    }
};