<?php

namespace App\Modules\Finance\Services\Banking;

use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\BankConnection;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankSyncService
{
    public function __construct(
        private IngBankingService $ingService,
    ) {}

    /**
     * Sync accounts from ING to local finance_accounts.
     */
    public function syncAccounts(BankConnection $connection): int
    {
        $ingAccounts = $this->ingService->fetchAccounts($connection);
        $count = 0;

        foreach ($ingAccounts as $ingAccount) {
            $externalId = $ingAccount['resourceId'] ?? $ingAccount['id'] ?? null;
            if (!$externalId) continue;

            $account = Account::firstOrNew([
                'bank_connection_id' => $connection->id,
                'external_account_id' => $externalId,
            ]);

            $account->fill([
                'user_id' => $connection->user_id,
                'name' => $ingAccount['name'] ?? $ingAccount['iban'] ?? 'ING Account',
                'type' => 'bank',
                'currency' => $ingAccount['currency'] ?? 'EUR',
                'is_active' => true,
            ]);

            // Update balance from ING
            $balances = $this->ingService->fetchBalances($connection, $externalId);
            foreach ($balances as $balance) {
                if (($balance['balanceType'] ?? '') === 'expected') {
                    $account->current_balance = $balance['balanceAmount']['amount'] ?? 0;
                }
            }

            $account->save();
            $count++;
        }

        return $count;
    }

    /**
     * Sync transactions from ING for a specific account.
     */
    public function syncTransactions(BankConnection $connection, Account $account, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        if (!$account->external_account_id) {
            return 0;
        }

        $dateFrom = $dateFrom ?? now()->subDays(30)->format('Y-m-d');

        $ingTransactions = $this->ingService->fetchTransactions(
            $connection,
            $account->external_account_id,
            $dateFrom,
            $dateTo
        );

        $booked = $ingTransactions['booked'] ?? $ingTransactions;
        $count = 0;

        foreach ($booked as $ingTx) {
            $externalId = $ingTx['transactionId'] ?? $ingTx['entryReference'] ?? null;

            // Skip if already imported
            if ($externalId && Transaction::where('external_id', $externalId)->exists()) {
                continue;
            }

            $amount = abs((float) ($ingTx['transactionAmount']['amount'] ?? 0));
            $isCredit = ($ingTx['transactionAmount']['amount'] ?? 0) >= 0;

            Transaction::create([
                'user_id' => $connection->user_id,
                'account_id' => $account->id,
                'type' => $isCredit ? 'income' : 'expense',
                'amount' => $amount,
                'description' => $ingTx['remittanceInformationUnstructured']
                    ?? $ingTx['creditorName']
                    ?? $ingTx['debtorName']
                    ?? 'ING Transaction',
                'date' => $ingTx['bookingDate'] ?? $ingTx['valueDate'] ?? now()->toDateString(),
                'external_id' => $externalId,
                'notes' => $ingTx['additionalInformation'] ?? null,
            ]);

            $count++;
        }

        // Recalculate account balance after sync
        $account->recalculateBalance();

        return $count;
    }

    /**
     * Full sync: all accounts + recent transactions.
     */
    public function fullSync(BankConnection $connection): array
    {
        $accountCount = $this->syncAccounts($connection);
        $txCount = 0;

        $accounts = $connection->accounts;
        foreach ($accounts as $account) {
            $txCount += $this->syncTransactions($connection, $account);
        }

        $connection->update(['last_synced_at' => now()]);

        Log::info("Bank sync completed", [
            'connection_id' => $connection->id,
            'accounts' => $accountCount,
            'transactions' => $txCount,
        ]);

        return [
            'accounts_synced' => $accountCount,
            'transactions_synced' => $txCount,
        ];
    }

    /**
     * Sync all active connections.
     */
    public function syncAllActive(): int
    {
        $connections = BankConnection::where('status', 'active')->get();
        $totalTx = 0;

        foreach ($connections as $connection) {
            try {
                $result = $this->fullSync($connection);
                $totalTx += $result['transactions_synced'];
            } catch (\Exception $e) {
                Log::error("Bank sync failed for connection {$connection->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $totalTx;
    }
}
