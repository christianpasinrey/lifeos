<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\CustomField;
use App\Modules\Tasks\Models\CustomFieldValue;
use App\Modules\Tasks\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SetTaskFieldValuesTool extends Tool
{
    protected string $description = 'Sets custom field values for a task. Validates fields belong to the task\'s board.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task')->required(),
            'field_values' => $schema->object()->description('Map of field_id to value, e.g. {"1": "some text", "2": 42}')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'task_id' => 'required|integer',
            'field_values' => 'required|array',
        ]);

        $task = Task::where('id', $request->get('task_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $task) {
            return Response::text('Error: Task not found or does not belong to you.');
        }

        $boardId = $task->column->board_id;
        $set = 0;

        foreach ($request->get('field_values') as $fieldId => $value) {
            $field = CustomField::where('id', $fieldId)
                ->where('board_id', $boardId)
                ->first();

            if (! $field) {
                continue;
            }

            if ($field->type === 'multi_select' && is_array($value)) {
                $value = json_encode($value);
            }

            CustomFieldValue::updateOrCreate(
                ['task_id' => $task->id, 'custom_field_id' => $field->id],
                ['value' => $value],
            );

            $set++;
        }

        return Response::text("Set {$set} custom field value(s) on task '{$task->title}' (ID: {$task->id}).");
    }
}
