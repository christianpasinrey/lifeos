<?php

namespace App\Modules\Habits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitBadge extends Model
{
    public $timestamps = false;

    protected $fillable = ['habit_id', 'badge_key', 'streak_value', 'earned_at'];

    protected $casts = [
        'earned_at' => 'datetime',
    ];

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    public static array $milestones = [
        7 => ['key' => 'streak_7', 'label' => '7 días', 'icon' => '🔥'],
        21 => ['key' => 'streak_21', 'label' => '21 días', 'icon' => '⭐'],
        30 => ['key' => 'streak_30', 'label' => '30 días', 'icon' => '💪'],
        60 => ['key' => 'streak_60', 'label' => '60 días', 'icon' => '🏆'],
        100 => ['key' => 'streak_100', 'label' => '100 días', 'icon' => '💎'],
        365 => ['key' => 'streak_365', 'label' => '1 año', 'icon' => '👑'],
    ];
}
