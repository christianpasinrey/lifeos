<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['issued', 'received']);
            $table->string('number', 50);
            $table->string('counterpart_name');
            $table->string('counterpart_tax_id')->nullable();
            $table->text('counterpart_address')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('retention_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'number']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('finance_invoices')->cascadeOnDelete();
            $table->string('concept', 255);
            $table->decimal('quantity', 10, 4)->default(1);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('subtotal', 12, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_line_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_line_id')->constrained('invoice_lines')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('finance_taxes');
            $table->foreignId('tax_rate_id')->constrained('finance_tax_rates');
            $table->string('tax_name', 100);
            $table->enum('tax_type', ['applicable', 'deductible']);
            $table->decimal('rate', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('invoice_transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('finance_invoices')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamp('created_at')->nullable();

            $table->unique(['invoice_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_transaction');
        Schema::dropIfExists('invoice_line_taxes');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('finance_invoices');
    }
};
