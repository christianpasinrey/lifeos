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
        'frequency', 'target_days', 'routine',
        'current_streak', 'best_streak',
        'is_active', 'sort_order', 'reminder_time',
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

    public function badges(): HasMany
    {
        return $this->hasMany(HabitBadge::class);
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(HabitVacation::class);
    }

    public function isOnVacationOn($date): bool
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;

        return $this->vacations()
            ->where('starts_at', '<=', $dateStr)
            ->where('ends_at', '>=', $dateStr)
            ->exists();
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
