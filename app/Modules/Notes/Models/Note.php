<?php

namespace App\Modules\Notes\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Note extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'folder_id', 'title', 'slug', 'content',
        'properties', 'is_bookmarked', 'sort_order',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_bookmarked' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $note) {
            if (empty($note->slug)) {
                $note->slug = Str::slug($note->title) ?: 'untitled';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(NoteFolder::class, 'folder_id');
    }

    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(NoteLink::class, 'source_note_id');
    }

    public function incomingLinks(): HasMany
    {
        return $this->hasMany(NoteLink::class, 'target_note_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(NoteTag::class, 'note_tag_pivot', 'note_id', 'tag_id');
    }

    public function noteables(): HasMany
    {
        return $this->hasMany(Noteable::class);
    }

    public function getAliasesAttribute(): array
    {
        return $this->properties['aliases'] ?? [];
    }

    public function isTemplate(): bool
    {
        return $this->properties['template'] ?? false;
    }
}
