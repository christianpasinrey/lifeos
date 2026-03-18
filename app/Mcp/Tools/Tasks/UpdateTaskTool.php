<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateTaskTool extends Tool
{
    protected string $description = 'Updates a task\'s title, description, priority, or due date.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task to update')->required(),
            'title' => $schema->string()->description('New title'),
            'description' => $schema->string()->description('New description'),
            'priority' => $schema->string()->description('New priority: low, medium, or high'),
            'due_date' => $schema->string()->description('New due date in YYYY-MM-DD format (use empty string to clear)'),
            'field_values' => $schema->object()->description('Optional custom field values: {field_id: value}'),
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
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date_format:Y-m-d',
        ]);

        $task = Task::where('id', $request->get('task_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $task) {
            return Response::text('Error: Task not found or does not belong to you.');
        }

        $data = [];
        foreach (['title', 'description', 'priority', 'due_date'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->get($field) ?: null;
            }
        }

        if (empty($data)) {
            return Response::text('Error: Provide at least one field to update.');
        }

        $task->update($data);

        if ($request->has('field_values') && is_array($request->get('field_values'))) {
            $boardId = $task->column->board_id;
            foreach ($request->get('field_values') as $fieldId => $value) {
                $field = \App\Modules\Tasks\Models\CustomField::where('id', $fieldId)
                    ->where('board_id', $boardId)->first();
                if (! $field) {
                    continue;
                }
                if ($field->type === 'multi_select' && is_array($value)) {
                    $value = json_encode($value);
                }
                \App\Modules\Tasks\Models\CustomFieldValue::updateOrCreate(
                    ['task_id' => $task->id, 'custom_field_id' => $field->id],
                    ['value' => $value],
                );
            }
        }

        return Response::text("Task '{$task->title}' updated (ID: {$task->id}).");
    }
}
