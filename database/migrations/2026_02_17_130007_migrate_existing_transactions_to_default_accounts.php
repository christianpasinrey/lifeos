<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get all users who have transactions
        $userIds = DB::table('transactions')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            // Create a default "General" account for each user
            $accountId = DB::table('finance_accounts')->insertGetId([
                'user_id' => $userId,
                'name' => 'General',
                'type' => 'other',
                'currency' => 'EUR',
                'initial_balance' => 0,
                'current_balance' => 0,
                'color' => '#6366f1',
                'icon' => null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Point all user's transactions to this account
            DB::table('transactions')
                ->where('user_id', $userId)
                ->whereNull('account_id')
                ->update(['account_id' => $accountId]);

            // Recalculate balance: income adds, expense subtracts
            $income = DB::table('transactions')
                ->where('user_id', $userId)
                ->where('account_id', $accountId)
                ->where('type', 'income')
                ->sum('amount');

            $expenses = DB::table('transactions')
                ->where('user_id', $userId)
                ->where('account_id', $accountId)
                ->where('type', 'expense')
                ->sum('amount');

            DB::table('finance_accounts')
                ->where('id', $accountId)
                ->update(['current_balance' => $income - $expenses]);
        }
    }

    public function down(): void
    {
        // Set all transactions back to null account
        DB::table('transactions')->update(['account_id' => null]);

        // Delete auto-created "General" accounts (those with sort_order 0 and name 'General')
        DB::table('finance_accounts')
            ->where('name', 'General')
            ->where('type', 'other')
            ->where('sort_order', 0)
            ->delete();
    }
};
