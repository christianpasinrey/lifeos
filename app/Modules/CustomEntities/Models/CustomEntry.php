<?php

namespace App\Modules\CustomEntities\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomEntry extends Model
{
    protected $fillable = ['custom_model_id', 'user_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function customModel(): BelongsTo
    {
        return $this->belongsTo(CustomModel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
