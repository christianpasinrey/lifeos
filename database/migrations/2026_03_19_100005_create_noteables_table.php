<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noteables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('noteable_type', 100);
            $table->unsignedBigInteger('noteable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['note_id', 'noteable_type', 'noteable_id']);
            $table->index(['noteable_type', 'noteable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noteables');
    }
};
