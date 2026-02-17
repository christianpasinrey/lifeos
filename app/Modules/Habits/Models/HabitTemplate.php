<?php

namespace App\Modules\Habits\Models;

use Illuminate\Database\Eloquent\Model;

class HabitTemplate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'category', 'icon', 'color', 'type',
        'unit', 'target_value', 'frequency', 'target_days',
        'description', 'sort_order',
    ];

    protected $casts = [
        'target_days' => 'array',
        'target_value' => 'decimal:2',
    ];
}
