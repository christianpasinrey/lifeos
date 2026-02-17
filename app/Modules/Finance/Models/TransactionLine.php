<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionLine extends Model
{
    protected $table = 'transaction_lines';

    protected $fillable = ['transaction_id', 'concept', 'quantity', 'unit_price', 'subtotal', 'sort_order'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(TransactionLineTax::class, 'transaction_line_id');
    }

    public function calculateSubtotal(): void
    {
        $this->subtotal = round($this->quantity * $this->unit_price, 2);
    }
}
