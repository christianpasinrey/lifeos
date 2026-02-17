<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class BankConnection extends Model
{
    protected $table = 'bank_connections';

    protected $fillable = [
        'user_id', 'provider', 'client_id', 'status',
        'access_token', 'refresh_token',
        'token_expires_at', 'consent_expires_at',
        'metadata', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'token_expires_at' => 'datetime',
            'consent_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    // Encrypt sensitive fields
    public function setClientIdAttribute($value): void
    {
        $this->attributes['client_id'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getClientIdAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certificates(): HasOne
    {
        return $this->hasOne(BankConnectionCertificate::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'bank_connection_id');
    }

    public function isTokenExpired(): bool
    {
        return !$this->token_expires_at || $this->token_expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isTokenExpired();
    }
}
