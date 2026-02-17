<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\InvoiceLine;
use App\Modules\Finance\Models\InvoiceLineTax;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private TaxService $taxService) {}

    public function getInvoices(User $user, array $filters = [])
    {
        $query = Invoice::where('user_id', $user->id)
            ->with('lines.taxes')
            ->orderBy('issue_date', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function createInvoice(User $user, array $data, array $lines): Invoice
    {
        return DB::transaction(function () use ($user, $data, $lines) {
            $data['user_id'] = $user->id;
            $data['number'] = $data['number'] ?? $this->generateNextNumber($user, $data['type']);

            $invoice = Invoice::create($data);
            $this->syncLines($invoice, $lines);
            $invoice->recalculateFromLines();

            return $invoice->fresh(['lines.taxes', 'transactions']);
        });
    }

    public function updateInvoice(Invoice $invoice, array $data, ?array $lines = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $lines) {
            $invoice->update($data);

            if ($lines !== null) {
                $this->syncLines($invoice, $lines);
                $invoice->recalculateFromLines();
            }

            return $invoice->fresh(['lines.taxes', 'transactions']);
        });
    }

    public function deleteInvoice(Invoice $invoice): void
    {
        $invoice->delete();
    }

    public function linkPayment(Invoice $invoice, Transaction $transaction, float $amount): void
    {
        $invoice->transactions()->attach($transaction->id, [
            'amount' => $amount,
            'created_at' => now(),
        ]);

        // Auto-update status
        if ($invoice->remaining_amount <= 0) {
            $invoice->update(['status' => 'paid']);
        }
    }

    public function unlinkPayment(Invoice $invoice, Transaction $transaction): void
    {
        $invoice->transactions()->detach($transaction->id);

        // Revert status if was paid
        if ($invoice->status === 'paid' && $invoice->remaining_amount > 0) {
            $invoice->update(['status' => 'sent']);
        }
    }

    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);
        return $invoice->fresh();
    }

    public function generateNextNumber(User $user, string $type): string
    {
        $year = now()->format('Y');
        $prefix = $type === 'issued' ? 'F' : 'R';

        $last = Invoice::where('user_id', $user->id)
            ->where('type', $type)
            ->where('number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(number, "-", -1) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($last) {
            $parts = explode('-', $last->number);
            $nextNum = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNum);
    }

    public function markOverdue(): int
    {
        return Invoice::where('status', 'sent')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    private function syncLines(Invoice $invoice, array $lines): void
    {
        $invoice->lines()->delete();

        foreach ($lines as $index => $lineData) {
            $line = InvoiceLine::create([
                'invoice_id' => $invoice->id,
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

                    InvoiceLineTax::create([
                        'invoice_line_id' => $line->id,
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
