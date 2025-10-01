<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('addon_package', function (Blueprint $table) {
            $table->integer('included_quantity')->default(0)->after('is_included');
        });
    }
    public function down(): void {
        Schema::table('addon_package', function (Blueprint $table) {
            $table->dropColumn('included_quantity');
        });
    }
};