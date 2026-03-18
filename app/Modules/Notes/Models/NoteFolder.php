<?php

namespace App\Modules\Notes\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NoteFolder extends Model
{
    protected $fillable = ['user_id', 'parent_id', 'name', 'slug', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(function (self $folder) {
            if (empty($folder->slug)) {
                $folder->slug = Str::slug($folder->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'folder_id')->orderBy('sort_order');
    }

    public function allNotes(): HasMany
    {
        return $this->hasMany(Note::class, 'folder_id');
    }
}
