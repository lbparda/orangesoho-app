<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('o2o_discount_package', function (Blueprint $table) {
            $table->id(); $table->foreignId('o2o_discount_id')->constrained()->onDelete('cascade'); $table->foreignId('package_id')->constrained()->onDelete('cascade'); $table->integer('subsidy_percentage'); $table->decimal('dho_payment', 8, 2); $table->decimal('osp_payment', 8, 2); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('o2o_discount_package'); }
};