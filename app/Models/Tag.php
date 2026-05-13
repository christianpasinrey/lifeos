<?php

namespace App\Models;

use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'color', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boards(): MorphToMany
    {
        return $this->morphedByMany(Board::class, 'taggable')->withPivot('created_at');
    }

    public function tasks(): MorphToMany
    {
        return $this->morphedByMany(Task::class, 'taggable')->withPivot('created_at');
    }
}
