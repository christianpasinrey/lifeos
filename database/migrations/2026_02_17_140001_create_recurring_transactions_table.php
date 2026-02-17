<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->enum('type', ['income', 'expense'])->default('expense');
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->integer('interval')->default(1);
            $table->tinyInteger('day_of_week')->nullable(); // 0=Sunday, 6=Saturday
            $table->tinyInteger('day_of_month')->nullable(); // 1-31
            $table->tinyInteger('month_of_year')->nullable(); // 1-12
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('recurring_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('concept', 255);
            $table->decimal('quantity', 10, 4)->default(1);
            $table->decimal('unit_price', 12, 4);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('recurring_transaction_line_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recurring_transaction_line_id');
            $table->foreign('recurring_transaction_line_id', 'rec_tx_line_taxes_line_id_fk')
                ->references('id')->on('recurring_transaction_lines')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('finance_taxes');
            $table->foreignId('tax_rate_id')->constrained('finance_tax_rates');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transaction_line_taxes');
        Schema::dropIfExists('recurring_transaction_lines');
        Schema::dropIfExists('recurring_transactions');
    }
};
