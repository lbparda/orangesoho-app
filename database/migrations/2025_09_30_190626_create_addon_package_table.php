<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('addon_package', function (Blueprint $table) {
            $table->id(); $table->foreignId('addon_id')->constrained()->onDelete('cascade'); $table->foreignId('package_id')->constrained()->onDelete('cascade'); $table->decimal('price', 8, 2); $table->boolean('is_included')->default(false); $table->integer('line_limit')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('addon_package'); }
};