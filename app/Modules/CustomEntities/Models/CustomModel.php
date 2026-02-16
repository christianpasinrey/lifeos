<?php

namespace App\Modules\CustomEntities\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static static create(array $attributes = [])
 */
class CustomModel extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'description', 'icon', 'color', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CustomAttribute::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CustomEntry::class);
    }
}
