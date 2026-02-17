<?php

namespace App\Modules\Calendar\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'sometimes|required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'all_day' => 'boolean',
            'color' => 'nullable|string|max:7',
            'location' => 'nullable|string|max:255',
            'type' => 'nullable|in:event,meeting,time_block',
        ];
    }
}
