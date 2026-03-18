<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MoveTaskTool extends Tool
{
    protected string $description = 'Moves a task to a different column (e.g., from "Por hacer" to "En curso"). Optionally set position.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task to move')->required(),
            'column_id' => $schema->integer()->description('The ID of the target column')->required(),
            'position' => $schema->integer()->description('Position in the target column (0 = top). Defaults to 0.'),
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
            'column_id' => 'required|integer',
            'position' => 'nullable|integer|min:0',
        ]);

        $task = Task::where('id', $request->get('task_id'))
            ->where('user_id', $user->id)
            ->with('column.board')
            ->first();

        if (! $task) {
            return Response::text('Error: Task not found or does not belong to you.');
        }

        $targetColumn = Column::where('id', $request->get('column_id'))
            ->where('board_id', $task->column->board_id)
            ->first();

        if (! $targetColumn) {
            return Response::text('Error: Target column not found or belongs to a different board.');
        }

        $position = $request->get('position', 0);

        if ($task->column_id === $targetColumn->id && $task->sort_order === $position) {
            return Response::text("Task '{$task->title}' is already at that position in '{$targetColumn->name}'.");
        }

        $oldColumn = $task->column->name;
        app(TaskService::class)->moveTask($task, $targetColumn->id, $position);

        if ($task->column_id === $targetColumn->id) {
            return Response::text("Task '{$task->title}' reordered to position {$position} in '{$targetColumn->name}'.");
        }

        return Response::text("Task '{$task->title}' moved from '{$oldColumn}' to '{$targetColumn->name}'.");
    }
}
