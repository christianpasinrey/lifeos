<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id', 'category_id', 'account_id', 'transfer_account_id',
        'type', 'amount', 'subtotal', 'tax_total', 'retention_total',
        'description', 'notes', 'date',
        'recurring_transaction_id', 'external_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'retention_total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class)->orderBy('sort_order');
    }

    public function getHasLinesAttribute(): bool
    {
        return $this->subtotal !== null;
    }

    public function recalculateFromLines(): void
    {
        $lines = $this->lines()->with('taxes')->get();

        if ($lines->isEmpty()) {
            $this->update([
                'subtotal' => null,
                'tax_total' => null,
                'retention_total' => null,
            ]);
            return;
        }

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
            'amount' => round($subtotal + $taxTotal - $retentionTotal, 2),
        ]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('transaction-attachments')
            ->useDisk('public');
    }
}
