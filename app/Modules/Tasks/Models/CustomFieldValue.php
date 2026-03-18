<?php

namespace App\Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CustomFieldValue extends Model
{
    protected $table = 'task_custom_field_values';

    protected $fillable = ['task_id', 'custom_field_id', 'value'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function castedValue(): mixed
    {
        if ($this->value === null) return null;

        return match ($this->field->type) {
            'number' => (float) $this->value,
            'checkbox' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'date' => Carbon::parse($this->value),
            'multi_select' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }
}
