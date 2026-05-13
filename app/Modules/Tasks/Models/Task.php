<?php

namespace App\Modules\Tasks\Models;

use App\Models\Concerns\HasTags;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Task extends Model implements HasMedia
{
    use HasTags, InteractsWithMedia;

    protected $table = 'tasks';

    protected $fillable = [
        'column_id', 'cycle_id', 'user_id', 'title', 'description', 'body_html',
        'due_date', 'priority', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('task-attachments')
            ->useDisk('public');
    }
}
