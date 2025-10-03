<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addon_package', function (Blueprint $table) {
            $table->decimal('included_line_commission', 8, 2)->default(0)->after('line_limit');
            $table->decimal('additional_line_commission', 8, 2)->default(0)->after('included_line_commission');
        });
    }

    public function down(): void
    {
        Schema::table('addon_package', function (Blueprint $table) {
            $table->dropColumn(['included_line_commission', 'additional_line_commission']);
        });
    }
};