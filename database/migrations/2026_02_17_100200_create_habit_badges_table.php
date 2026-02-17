<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->string('badge_key', 50);
            $table->unsignedInteger('streak_value');
            $table->timestamp('earned_at');
            $table->unique(['habit_id', 'badge_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_badges');
    }
};
