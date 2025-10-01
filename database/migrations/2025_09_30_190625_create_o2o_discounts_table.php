<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('o2o_discounts', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->decimal('total_discount_amount', 8, 2); $table->integer('duration_months'); $table->decimal('commission_cost', 8, 2); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('o2o_discounts'); }
};