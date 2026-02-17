<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\Account;

class AccountService
{
    public function getAccounts(User $user, bool $activeOnly = true)
    {
        $query = $user->financeAccounts()->orderBy('sort_order');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function createAccount(User $user, array $data): Account
    {
        $data['user_id'] = $user->id;
        $data['current_balance'] = $data['initial_balance'] ?? 0;

        return Account::create($data);
    }

    public function updateAccount(Account $account, array $data): Account
    {
        $account->update($data);
        return $account->fresh();
    }

    public function deleteAccount(Account $account): void
    {
        // Nullify transactions pointing to this account
        $account->transactions()->update(['account_id' => null]);
        $account->incomingTransfers()->update(['transfer_account_id' => null]);
        $account->delete();
    }

    public function recalculateBalance(Account $account): Account
    {
        $account->recalculateBalance();
        return $account->fresh();
    }

    public function getTotalBalance(User $user): float
    {
        return (float) $user->financeAccounts()
            ->where('is_active', true)
            ->sum('current_balance');
    }
}
