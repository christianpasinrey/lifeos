<?php

namespace App\Modules\CustomEntities\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomAttribute extends Model
{
    protected $fillable = [
        'custom_model_id', 'name', 'slug', 'type', 'options',
        'is_required', 'default_value', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function customModel(): BelongsTo
    {
        return $this->belongsTo(CustomModel::class);
    }
}
