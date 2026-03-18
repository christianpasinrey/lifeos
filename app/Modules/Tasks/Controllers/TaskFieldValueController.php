<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\CustomField;
use App\Modules\Tasks\Models\CustomFieldValue;
use App\Modules\Tasks\Models\Task;
use Illuminate\Http\Request;

class TaskFieldValueController extends Controller
{
    public function update(Request $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'values' => 'required|array',
            'values.*.field_id' => 'required|integer',
            'values.*.value' => 'nullable',
        ]);

        $boardId = $task->column->board_id;

        foreach ($data['values'] as $entry) {
            $field = CustomField::where('id', $entry['field_id'])
                ->where('board_id', $boardId)
                ->first();

            if (!$field) continue;

            $value = $entry['value'];
            if ($field->type === 'multi_select' && is_array($value)) {
                $value = json_encode($value);
            }

            CustomFieldValue::updateOrCreate(
                ['task_id' => $task->id, 'custom_field_id' => $field->id],
                ['value' => $value],
            );
        }

        return response()->json([
            'data' => $task->fresh()->load('customFieldValues.field'),
        ]);
    }
}
