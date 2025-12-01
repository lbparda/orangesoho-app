<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('pyme_o2o_discounts', function (Blueprint $table) {
        // Porcentajes de penalización (merma) según permanencia
        // Usamos decimal con precisión para porcentajes (ej: 5.00)
        $table->decimal('penalty_12m', 5, 2)->default(0)->after('percentage');
        $table->decimal('penalty_24m', 5, 2)->default(0)->after('penalty_12m');
        $table->decimal('penalty_36m', 5, 2)->default(0)->after('penalty_24m');
    });
}

public function down()
{
    Schema::table('pyme_o2o_discounts', function (Blueprint $table) {
        $table->dropColumn(['penalty_12m', 'penalty_24m', 'penalty_36m']);
    });
}
};
