<?php

namespace App\Modules\Tasks\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Board extends Model
{
    protected $table = 'task_boards';

    protected $fillable = ['user_id', 'name', 'description', 'sort_order'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class, 'board_id')->orderBy('sort_order');
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Column::class, 'board_id', 'column_id');
    }
}
