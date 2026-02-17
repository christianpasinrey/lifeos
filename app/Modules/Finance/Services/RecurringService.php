<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\RecurringTransaction;
use App\Modules\Finance\Models\RecurringTransactionLine;
use App\Modules\Finance\Models\RecurringTransactionLineTax;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringService
{
    public function __construct(
        private TransactionService $transactionService,
        private TaxService $taxService,
    ) {}

    public function getRecurring(User $user, bool $activeOnly = true)
    {
        $query = RecurringTransaction::where('user_id', $user->id)
            ->with('account', 'category', 'lines.taxes')
            ->orderBy('next_due_date');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function createRecurring(User $user, array $data, ?array $lines = null): RecurringTransaction
    {
        return DB::transaction(function () use ($user, $data, $lines) {
            $data['user_id'] = $user->id;
            $data['next_due_date'] = $data['next_due_date'] ?? $data['start_date'];

            $recurring = RecurringTransaction::create($data);

            if ($lines) {
                $this->syncLines($recurring, $lines);
            }

            return $recurring->fresh(['account', 'category', 'lines.taxes']);
        });
    }

    public function updateRecurring(RecurringTransaction $recurring, array $data, ?array $lines = null): RecurringTransaction
    {
        return DB::transaction(function () use ($recurring, $data, $lines) {
            $recurring->update($data);

            if ($lines !== null) {
                $this->syncLines($recurring, $lines);
            }

            return $recurring->fresh(['account', 'category', 'lines.taxes']);
        });
    }

    public function deleteRecurring(RecurringTransaction $recurring): void
    {
        $recurring->delete();
    }

    public function generateDueTransactions(): int
    {
        $due = RecurringTransaction::where('is_active', true)
            ->where('next_due_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->with('lines.taxes')
            ->get();

        $count = 0;

        foreach ($due as $recurring) {
            $this->generateTransaction($recurring);
            $recurring->update([
                'last_generated_at' => now(),
                'next_due_date' => $this->computeNextDueDate($recurring),
            ]);
            $count++;
        }

        return $count;
    }

    private function generateTransaction(RecurringTransaction $recurring): void
    {
        $data = [
            'type' => $recurring->type,
            'amount' => $recurring->amount,
            'description' => $recurring->description,
            'notes' => $recurring->notes,
            'category_id' => $recurring->category_id,
            'account_id' => $recurring->account_id,
            'date' => $recurring->next_due_date->toDateString(),
            'recurring_transaction_id' => $recurring->id,
        ];

        $lines = null;
        if ($recurring->lines->isNotEmpty()) {
            $lines = $recurring->lines->map(function ($line) {
                return [
                    'concept' => $line->concept,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'sort_order' => $line->sort_order,
                    'taxes' => $line->taxes->map(fn ($t) => [
                        'tax_id' => $t->tax_id,
                        'tax_rate_id' => $t->tax_rate_id,
                    ])->toArray(),
                ];
            })->toArray();
        }

        $this->transactionService->createTransaction($recurring->user, $data, $lines);
    }

    public function computeNextDueDate(RecurringTransaction $recurring): Carbon
    {
        $current = $recurring->next_due_date->copy();

        return match ($recurring->frequency) {
            'daily' => $current->addDays($recurring->interval),
            'weekly' => $current->addWeeks($recurring->interval),
            'monthly' => $current->addMonths($recurring->interval),
            'yearly' => $current->addYears($recurring->interval),
        };
    }

    public function getNextOccurrences(RecurringTransaction $recurring, int $count = 5): array
    {
        $dates = [];
        $date = $recurring->next_due_date->copy();

        for ($i = 0; $i < $count; $i++) {
            if ($recurring->end_date && $date->gt($recurring->end_date)) {
                break;
            }

            $dates[] = $date->toDateString();

            $date = match ($recurring->frequency) {
                'daily' => $date->addDays($recurring->interval),
                'weekly' => $date->addWeeks($recurring->interval),
                'monthly' => $date->addMonths($recurring->interval),
                'yearly' => $date->addYears($recurring->interval),
            };
        }

        return $dates;
    }

    private function syncLines(RecurringTransaction $recurring, array $lines): void
    {
        $recurring->lines()->delete();

        foreach ($lines as $index => $lineData) {
            $line = RecurringTransactionLine::create([
                'recurring_transaction_id' => $recurring->id,
                'concept' => $lineData['concept'],
                'quantity' => $lineData['quantity'] ?? 1,
                'unit_price' => $lineData['unit_price'],
                'sort_order' => $lineData['sort_order'] ?? $index,
            ]);

            if (!empty($lineData['taxes'])) {
                foreach ($lineData['taxes'] as $taxData) {
                    RecurringTransactionLineTax::create([
                        'recurring_transaction_line_id' => $line->id,
                        'tax_id' => $taxData['tax_id'],
                        'tax_rate_id' => $taxData['tax_rate_id'],
                    ]);
                }
            }
        }
    }
}
