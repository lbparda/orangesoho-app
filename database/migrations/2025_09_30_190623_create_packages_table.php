<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('packages', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->decimal('base_price', 8, 2); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('packages'); }
};