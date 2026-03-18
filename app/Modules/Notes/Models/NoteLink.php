<?php

namespace App\Modules\Notes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_note_id', 'target_note_id', 'target_title',
        'link_type', 'context', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'source_note_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'target_note_id');
    }
}
