<?php

namespace App\Modules\Tasks\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        // On create the title is required; on update every field is optional
        // (clients send partial payloads for inline edits — priority, due_date,
        // a single tag toggle, the rich body, etc.).
        return [
            'title' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description' => 'sometimes|nullable|string|max:5000',
            'body_html' => 'sometimes|nullable|string|max:65535',
            'due_date' => 'sometimes|nullable|date',
            'priority' => 'sometimes|nullable|in:low,medium,high',
            'cycle_id' => 'sometimes|nullable|integer',
            'tag_ids' => 'sometimes|nullable|array',
            'tag_ids.*' => 'integer',
            'tag_names' => 'sometimes|nullable|array',
            'tag_names.*' => 'string|max:100',
            'replace_tags' => 'sometimes|nullable|boolean',
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
