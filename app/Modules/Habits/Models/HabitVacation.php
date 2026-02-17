<?php

namespace App\Modules\Habits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitVacation extends Model
{
    protected $fillable = ['habit_id', 'starts_at', 'ends_at', 'reason'];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
