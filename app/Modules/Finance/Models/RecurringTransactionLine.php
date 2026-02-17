<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTransactionLine extends Model
{
    protected $table = 'recurring_transaction_lines';

    protected $fillable = [
        'recurring_transaction_id', 'concept', 'quantity', 'unit_price', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(RecurringTransactionLineTax::class, 'recurring_transaction_line_id');
    }
}
