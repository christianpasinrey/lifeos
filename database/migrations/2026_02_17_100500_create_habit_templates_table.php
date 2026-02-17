<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 50);
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->default('#6366f1');
            $table->string('type', 20)->default('boolean');
            $table->string('unit', 50)->nullable();
            $table->decimal('target_value', 10, 2)->nullable();
            $table->string('frequency', 20)->default('daily');
            $table->json('target_days')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_templates');
    }
};
