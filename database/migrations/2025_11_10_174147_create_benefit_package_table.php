<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_package', function (Blueprint $table) {
            $table->foreignId('benefit_id')->constrained('benefits')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->primary(['benefit_id', 'package_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('benefit_package'); }
};