<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateTaskTool extends Tool
{
    protected string $description = 'Creates a new task in a specific column.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'column_id' => $schema->integer()->description('The ID of the column to create the task in')->required(),
            'title' => $schema->string()->description('Task title')->required(),
            'description' => $schema->string()->description('Task description'),
            'priority' => $schema->string()->description('Priority: low, medium (default), or high'),
            'due_date' => $schema->string()->description('Due date in YYYY-MM-DD format'),
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
            'column_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date_format:Y-m-d',
        ]);

        $column = Column::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->find($request->get('column_id'));

        if (! $column) {
            return Response::text('Error: Column not found or does not belong to you.');
        }

        $data = array_filter([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'priority' => $request->get('priority', 'medium'),
            'due_date' => $request->get('due_date'),
        ], fn ($v) => $v !== null);

        $task = app(TaskService::class)->createTask($column, $user, $data);

        if ($request->has('field_values') && is_array($request->get('field_values'))) {
            $boardId = $column->board_id;
            foreach ($request->get('field_values') as $fieldId => $value) {
                $field = \App\Modules\Tasks\Models\CustomField::where('id', $fieldId)
                    ->where('board_id', $boardId)->first();
                if (! $field) {
                    continue;
                }
                if ($field->type === 'multi_select' && is_array($value)) {
                    $value = json_encode($value);
                }
                \App\Modules\Tasks\Models\CustomFieldValue::create([
                    'task_id' => $task->id,
                    'custom_field_id' => $field->id,
                    'value' => $value,
                ]);
            }
        }

        return Response::text("Task '{$task->title}' created (ID: {$task->id}) in column '{$column->name}'.");
    }
}
