<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_id')->constrained('finance_taxes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_tax_rates');
    }
};
