<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'finance_invoices';

    protected $fillable = [
        'user_id', 'type', 'number', 'counterpart_name',
        'counterpart_tax_id', 'counterpart_address',
        'issue_date', 'due_date', 'status',
        'subtotal', 'tax_total', 'retention_total', 'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'retention_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id')->orderBy('sort_order');
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'invoice_transaction')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function recalculateFromLines(): void
    {
        $lines = $this->lines()->with('taxes')->get();

        $subtotal = 0;
        $taxTotal = 0;
        $retentionTotal = 0;

        foreach ($lines as $line) {
            $subtotal += (float) $line->subtotal;
            foreach ($line->taxes as $tax) {
                if ($tax->tax_type === 'applicable') {
                    $taxTotal += (float) $tax->amount;
                } else {
                    $retentionTotal += (float) $tax->amount;
                }
            }
        }

        $this->update([
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'retention_total' => round($retentionTotal, 2),
            'total' => round($subtotal + $taxTotal - $retentionTotal, 2),
        ]);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->transactions()->sum('invoice_transaction.amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total - $this->paid_amount;
    }
}
