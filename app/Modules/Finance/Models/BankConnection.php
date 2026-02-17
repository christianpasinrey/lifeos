<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankConnection extends Model
{
    protected $table = 'bank_connections';

    protected $fillable = [
        'user_id', 'provider', 'status',
        'requisition_id', 'agreement_id',
        'institution_id', 'institution_name', 'institution_logo',
        'reference',
        'consent_expires_at', 'metadata', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'consent_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'bank_connection_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isConsentExpired();
    }

    public function isConsentExpired(): bool
    {
        return $this->consent_expires_at && $this->consent_expires_at->isPast();
    }
}
