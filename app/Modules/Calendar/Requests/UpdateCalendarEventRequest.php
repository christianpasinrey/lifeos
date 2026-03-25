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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'type' => 'nullable|in:event,meeting,time_block',
            'recurrence_rule' => 'nullable|array',
            'recurrence_rule.freq' => 'required_with:recurrence_rule|in:daily,weekly,monthly,yearly',
            'recurrence_rule.interval' => 'nullable|integer|min:1',
            'recurrence_rule.until' => 'nullable|date',
            'recurrence_rule.count' => 'nullable|integer|min:1',
            'reminder' => 'nullable|in:none,5m,15m,30m,1h,1d',
            'attendees' => 'nullable|array',
            'attendees.*' => 'string|max:255',
        ];
    }
}
