<?php

namespace App\Modules\Finance\Services\Banking;

use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\BankConnection;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\Log;

class BankSyncService
{
    public function __construct(
        private GoCardlessBankingService $gcService,
    ) {}

    /**
     * Sync accounts from GoCardless requisition to local finance_accounts.
     */
    public function syncAccounts(BankConnection $connection): int
    {
        $requisition = $this->gcService->getRequisition($connection->requisition_id);

        // Check if requisition is still linked
        $status = $this->gcService->mapRequisitionStatus($requisition['status'] ?? '');
        if ($status !== 'active') {
            $connection->update(['status' => $status]);
            return 0;
        }

        $accountIds = $requisition['accounts'] ?? [];
        $count = 0;

        foreach ($accountIds as $gcAccountId) {
            try {
                $details = $this->gcService->getAccountDetails($gcAccountId);
                $balances = $this->gcService->getAccountBalances($gcAccountId);

                $account = Account::firstOrNew([
                    'bank_connection_id' => $connection->id,
                    'external_account_id' => $gcAccountId,
                ]);

                $account->fill([
                    'user_id' => $connection->user_id,
                    'name' => $details['ownerName'] ?? $details['iban'] ?? $connection->institution_name ?? 'Bank Account',
                    'type' => 'bank',
                    'currency' => $details['currency'] ?? 'EUR',
                    'is_active' => true,
                ]);

                // Pick best available balance
                foreach ($balances as $balance) {
                    $type = $balance['balanceType'] ?? '';
                    if (in_array($type, ['interimAvailable', 'expected', 'closingBooked'])) {
                        $account->current_balance = (float) ($balance['balanceAmount']['amount'] ?? 0);
                        break;
                    }
                }

                // Fallback: use first balance if none of the preferred types matched
                if (!$account->exists && !empty($balances[0])) {
                    $account->current_balance = (float) ($balances[0]['balanceAmount']['amount'] ?? 0);
                }

                $account->save();
                $count++;
            } catch (\Exception $e) {
                Log::warning("GoCardless: failed to sync account {$gcAccountId}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Sync transactions for a specific account.
     */
    public function syncTransactions(BankConnection $connection, Account $account, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        if (!$account->external_account_id) {
            return 0;
        }

        $dateFrom = $dateFrom ?? now()->subDays(30)->format('Y-m-d');

        $transactions = $this->gcService->fetchTransactions(
            $account->external_account_id,
            $dateFrom,
            $dateTo
        );

        $booked = $transactions['booked'] ?? $transactions;
        $count = 0;

        foreach ($booked as $tx) {
            $externalId = $tx['transactionId']
                ?? $tx['internalTransactionId']
                ?? $tx['entryReference']
                ?? null;

            // Skip if already imported
            if ($externalId && Transaction::where('external_id', $externalId)->exists()) {
                continue;
            }

            $rawAmount = (float) ($tx['transactionAmount']['amount'] ?? 0);
            $amount = abs($rawAmount);
            $isCredit = $rawAmount >= 0;

            Transaction::create([
                'user_id' => $connection->user_id,
                'account_id' => $account->id,
                'type' => $isCredit ? 'income' : 'expense',
                'amount' => $amount,
                'description' => $tx['remittanceInformationUnstructured']
                    ?? $tx['remittanceInformationStructured']
                    ?? $tx['creditorName']
                    ?? $tx['debtorName']
                    ?? 'Bank Transaction',
                'date' => $tx['bookingDate'] ?? $tx['valueDate'] ?? now()->toDateString(),
                'external_id' => $externalId,
                'notes' => $tx['additionalInformation'] ?? null,
            ]);

            $count++;
        }

        if ($count > 0) {
            $account->recalculateBalance();
        }

        return $count;
    }

    /**
     * Full sync: all accounts + recent transactions.
     */
    public function fullSync(BankConnection $connection): array
    {
        $accountCount = $this->syncAccounts($connection);
        $txCount = 0;

        foreach ($connection->accounts()->get() as $account) {
            $txCount += $this->syncTransactions($connection, $account);
        }

        $connection->update(['last_synced_at' => now()]);

        Log::info('Bank sync completed', [
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
     * Sync all active connections. Check requisition status first.
     */
    public function syncAllActive(): int
    {
        $connections = BankConnection::where('status', 'active')->get();
        $totalTx = 0;

        foreach ($connections as $connection) {
            try {
                // Check consent expiry
                if ($connection->isConsentExpired()) {
                    $connection->update(['status' => 'expired']);
                    Log::info("Bank connection {$connection->id} consent expired");
                    continue;
                }

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
