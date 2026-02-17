<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionLine;
use App\Modules\Finance\Models\TransactionLineTax;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private TaxService $taxService,
        private AccountService $accountService,
    ) {}

    public function getTransactions(User $user, array $filters = [])
    {
        $query = $user->transactions()
            ->with('category', 'media', 'account', 'lines.taxes')
            ->orderBy('date', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    public function createTransaction(User $user, array $data, ?array $lines = null): Transaction
    {
        return DB::transaction(function () use ($user, $data, $lines) {
            $data['user_id'] = $user->id;
            $data['date'] = $data['date'] ?? now()->toDateString();

            $transaction = Transaction::create($data);

            if ($lines) {
                $this->syncLines($transaction, $lines);
                $transaction->recalculateFromLines();
            }

            // Update account balance
            if ($transaction->account_id) {
                $transaction->account->recalculateBalance();
            }
            if ($transaction->transfer_account_id) {
                $transaction->transferAccount->recalculateBalance();
            }

            return $transaction->fresh(['category', 'media', 'account', 'lines.taxes']);
        });
    }

    public function updateTransaction(Transaction $transaction, array $data, ?array $lines = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $data, $lines) {
            $oldAccountId = $transaction->account_id;
            $oldTransferAccountId = $transaction->transfer_account_id;

            $transaction->update($data);

            if ($lines !== null) {
                $this->syncLines($transaction, $lines);
                $transaction->recalculateFromLines();
            }

            // Recalculate balances for old and new accounts
            $accountIds = array_unique(array_filter([
                $oldAccountId,
                $oldTransferAccountId,
                $transaction->account_id,
                $transaction->transfer_account_id,
            ]));

            foreach ($accountIds as $accountId) {
                $account = \App\Modules\Finance\Models\Account::find($accountId);
                $account?->recalculateBalance();
            }

            return $transaction->fresh(['category', 'media', 'account', 'lines.taxes']);
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $accountId = $transaction->account_id;
            $transferAccountId = $transaction->transfer_account_id;

            $transaction->delete();

            if ($accountId) {
                $account = \App\Modules\Finance\Models\Account::find($accountId);
                $account?->recalculateBalance();
            }
            if ($transferAccountId) {
                $account = \App\Modules\Finance\Models\Account::find($transferAccountId);
                $account?->recalculateBalance();
            }
        });
    }

    public function createTransfer(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'transfer',
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'Transferencia entre cuentas',
                'notes' => $data['notes'] ?? null,
                'account_id' => $data['from_account_id'],
                'transfer_account_id' => $data['to_account_id'],
                'date' => $data['date'] ?? now()->toDateString(),
            ]);

            $transaction->account->recalculateBalance();
            $transaction->transferAccount->recalculateBalance();

            return $transaction->fresh(['account', 'transferAccount']);
        });
    }

    /**
     * Sync line items for a transaction.
     */
    private function syncLines(Transaction $transaction, array $lines): void
    {
        // Delete existing lines (cascade deletes taxes)
        $transaction->lines()->delete();

        foreach ($lines as $index => $lineData) {
            $line = TransactionLine::create([
                'transaction_id' => $transaction->id,
                'concept' => $lineData['concept'],
                'quantity' => $lineData['quantity'] ?? 1,
                'unit_price' => $lineData['unit_price'],
                'subtotal' => round(($lineData['quantity'] ?? 1) * $lineData['unit_price'], 2),
                'sort_order' => $lineData['sort_order'] ?? $index,
            ]);

            if (!empty($lineData['taxes'])) {
                foreach ($lineData['taxes'] as $taxData) {
                    $snapshot = $this->taxService->snapshotTaxData($taxData['tax_id'], $taxData['tax_rate_id']);
                    $amount = round($line->subtotal * ($snapshot['rate'] / 100), 2);

                    TransactionLineTax::create([
                        'transaction_line_id' => $line->id,
                        'tax_id' => $snapshot['tax_id'],
                        'tax_rate_id' => $snapshot['tax_rate_id'],
                        'tax_name' => $snapshot['tax_name'],
                        'tax_type' => $snapshot['tax_type'],
                        'rate' => $snapshot['rate'],
                        'amount' => $amount,
                    ]);
                }
            }
        }
    }
}
