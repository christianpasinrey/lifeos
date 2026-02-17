<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLineTax extends Model
{
    protected $table = 'transaction_line_taxes';

    protected $fillable = [
        'transaction_line_id', 'tax_id', 'tax_rate_id',
        'tax_name', 'tax_type', 'rate', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(TransactionLine::class, 'transaction_line_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function calculateAmount(float $lineSubtotal): void
    {
        $this->amount = round($lineSubtotal * ($this->rate / 100), 2);
    }
}
