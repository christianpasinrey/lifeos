<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_line_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_line_id')->constrained('transaction_lines')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('finance_taxes')->restrictOnDelete();
            $table->foreignId('tax_rate_id')->constrained('finance_tax_rates')->restrictOnDelete();
            $table->string('tax_name', 100);
            $table->enum('tax_type', ['applicable', 'deductible']);
            $table->decimal('rate', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_line_taxes');
    }
};
