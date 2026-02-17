<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('category_id')
                ->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('transfer_account_id')->nullable()->after('account_id')
                ->constrained('finance_accounts')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->nullable()->after('amount');
            $table->decimal('tax_total', 12, 2)->nullable()->after('subtotal');
            $table->decimal('retention_total', 12, 2)->nullable()->after('tax_total');
            $table->foreignId('recurring_transaction_id')->nullable()->after('retention_total');
            $table->string('external_id', 255)->nullable()->after('recurring_transaction_id');
        });

        // Modify type enum to include 'transfer'
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('income', 'expense', 'transfer') NOT NULL DEFAULT 'expense'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('income', 'expense') NOT NULL DEFAULT 'expense'");

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['transfer_account_id']);
            $table->dropColumn([
                'account_id',
                'transfer_account_id',
                'subtotal',
                'tax_total',
                'retention_total',
                'recurring_transaction_id',
                'external_id',
            ]);
        });
    }
};
