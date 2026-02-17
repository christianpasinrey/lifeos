<?php

namespace App\Modules\Admin\Models;

use App\Models\User;
use App\Modules\Admin\ModuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModule extends Model
{
    protected $table = 'user_modules';

    protected $fillable = ['user_id', 'module', 'is_active', 'plan', 'limits'];

    protected $casts = [
        'is_active' => 'boolean',
        'limits' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPremium(): bool
    {
        return $this->plan === 'premium';
    }

    public function getLimit(string $key): ?int
    {
        if ($this->isPremium()) {
            return null; // sin límite
        }

        $limits = $this->limits ?? ModuleRegistry::freeLimits($this->module);

        return $limits[$key] ?? null;
    }
}
