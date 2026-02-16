<?php

namespace App\Modules\Habits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitLog extends Model
{
    protected $fillable = ['habit_id', 'completed_at', 'value', 'notes'];

    protected $casts = [
        'completed_at' => 'date',
        'value' => 'decimal:2',
    ];

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
