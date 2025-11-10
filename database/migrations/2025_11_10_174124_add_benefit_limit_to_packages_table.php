<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // "Elige 1, 2 o 3"
            $table->integer('benefit_limit')->default(0)->after('base_price');
        });
    }
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('benefit_limit');
        });
    }
};