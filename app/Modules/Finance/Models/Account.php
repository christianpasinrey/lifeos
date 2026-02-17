<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'finance_accounts';

    protected $fillable = [
        'user_id', 'name', 'type', 'currency', 'initial_balance',
        'current_balance', 'color', 'icon', 'is_active', 'sort_order',
        'bank_connection_id', 'external_account_id',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_account_id');
    }

    public function recalculateBalance(): void
    {
        $income = $this->transactions()->where('type', 'income')->sum('amount');
        $expenses = $this->transactions()->where('type', 'expense')->sum('amount');

        // Transfers: outgoing (as source account) subtract, incoming (as transfer_account) add
        $transfersOut = $this->transactions()->where('type', 'transfer')->sum('amount');
        $transfersIn = $this->incomingTransfers()->where('type', 'transfer')->sum('amount');

        $this->update([
            'current_balance' => $this->initial_balance + $income - $expenses - $transfersOut + $transfersIn,
        ]);
    }
}
