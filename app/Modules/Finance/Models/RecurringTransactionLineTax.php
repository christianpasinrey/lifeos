<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransactionLineTax extends Model
{
    protected $table = 'recurring_transaction_line_taxes';

    protected $fillable = [
        'recurring_transaction_line_id', 'tax_id', 'tax_rate_id',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(RecurringTransactionLine::class, 'recurring_transaction_line_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}
