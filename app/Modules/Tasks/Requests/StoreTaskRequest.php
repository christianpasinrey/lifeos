<?php

namespace App\Modules\Tasks\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'body_html' => 'nullable|string|max:65535',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'cycle_id' => 'nullable|integer|exists:task_cycles,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
            'replace_tags' => 'nullable|boolean',
        ];
    }

    /**
     * Returns only the columns that belong directly on the task row.
     */
    public function persistedAttributes(): array
    {
        return collect($this->validated())
            ->only(['title', 'description', 'body_html', 'due_date', 'priority', 'cycle_id'])
            ->all();
    }
}
