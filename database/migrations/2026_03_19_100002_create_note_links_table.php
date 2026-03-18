<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('target_note_id')->nullable()->constrained('notes')->nullOnDelete();
            $table->string('target_title')->nullable();
            $table->enum('link_type', ['wikilink', 'embed']);
            $table->string('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('target_note_id');
            $table->index('target_title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_links');
    }
};
