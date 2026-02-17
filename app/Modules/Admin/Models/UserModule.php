<?php

namespace App\Modules\Admin\Models;

use App\Models\User;
use App\Modules\Admin\ModuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModule extends Model
{
    protected $table = 'user_modules';

    protected $fillable = ['user_id', 'module', 'is_active', 'plan', 'limits', 'features'];

    protected $casts = [
        'is_active' => 'boolean',
        'limits' => 'array',
        'features' => 'array',
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

    public function hasFeature(string $key): bool
    {
        if ($this->isPremium()) {
            return true;
        }

        if ($this->features && array_key_exists($key, $this->features)) {
            return (bool) $this->features[$key];
        }

        $defaults = ModuleRegistry::freeFeatures($this->module);

        return (bool) ($defaults[$key] ?? false);
    }

    public function resolvedFeatures(): array
    {
        $defaults = ModuleRegistry::freeFeatures($this->module);

        if ($this->isPremium()) {
            return array_map(fn() => true, $defaults);
        }

        $custom = $this->features ?? [];

        return array_merge($defaults, $custom);
    }
}
