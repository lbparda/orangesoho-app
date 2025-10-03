<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('package_terminal', function (Blueprint $table) {
            $table->decimal('included_line_commission', 8, 2)->nullable()->after('duration_months');
            $table->decimal('additional_line_commission', 8, 2)->nullable()->after('included_line_commission');
        });
    }

    public function down()
    {
        Schema::table('package_terminal', function (Blueprint $table) {
            $table->dropColumn(['included_line_commission', 'additional_line_commission']);
        });
    }
};