<?php

namespace App\Modules\Habits\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHabitRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'type' => 'in:boolean,numeric',
            'unit' => 'nullable|string|max:50',
            'target_value' => 'nullable|numeric|min:0',
            'frequency' => 'in:daily,weekly,custom',
            'target_days' => 'nullable|array',
            'target_days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'routine' => 'in:morning,afternoon,evening,anytime',
            'reminder_time' => 'nullable|date_format:H:i',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
