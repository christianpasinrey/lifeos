<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('task_boards')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->text('description')->nullable();
            $table->string('color', 9)->default('#6366f1');
            $table->enum('status', ['planned', 'active', 'completed'])->default('planned');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['board_id', 'slug']);
            $table->index(['board_id', 'status']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('cycle_id')
                ->nullable()
                ->after('column_id')
                ->constrained('task_cycles')
                ->nullOnDelete();

            $table->index('cycle_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cycle_id');
        });

        Schema::dropIfExists('task_cycles');
    }
};
