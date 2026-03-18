<?php

namespace App\Modules\Notes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Noteable extends Model
{
    public $timestamps = false;

    protected $fillable = ['note_id', 'noteable_type', 'noteable_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
