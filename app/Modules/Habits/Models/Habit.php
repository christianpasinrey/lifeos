<?php

namespace App\Modules\Habits\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'icon', 'color',
        'type', 'unit', 'target_value',
        'frequency', 'target_days',
        'current_streak', 'best_streak',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'target_days' => 'array',
        'target_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    public function isDueToday(): bool
    {
        return $this->isDueOnDate(now());
    }

    public function isDueOnDate($date): bool
    {
        $day = strtolower($date->format('D'));
        return match ($this->frequency) {
            'daily' => true,
            'weekly', 'custom' => in_array($day, $this->target_days ?? []),
            default => true,
        };
    }

    public function isCompletedOn(string $date): bool
    {
        if ($this->type === 'numeric') {
            $log = $this->logs()->where('completed_at', $date)->first();
            return $log && $this->target_value && $log->value >= $this->target_value;
        }

        return $this->logs()->where('completed_at', $date)->exists();
    }
}
