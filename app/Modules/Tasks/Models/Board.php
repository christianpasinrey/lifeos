<?php

namespace App\Modules\Tasks\Models;

use App\Models\Concerns\HasTags;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Board extends Model
{
    use HasTags;

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

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class, 'board_id')->orderBy('sort_order');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class, 'board_id')->orderBy('sort_order');
    }
}
