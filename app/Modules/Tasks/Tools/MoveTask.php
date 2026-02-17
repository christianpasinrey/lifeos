<?php

namespace App\Modules\Tasks\Tools;

use App\Models\User;
use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class MoveTask implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Mueve una tarea a otra columna del tablero (por ejemplo, de "Por hacer" a "En curso").';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()
                ->description('ID de la tarea a mover')
                ->required(),
            'target_column_id' => $schema->integer()
                ->description('ID de la columna de destino')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $taskId = $request->integer('task_id');
        $targetColumnId = $request->integer('target_column_id');

        // Verify task exists and belongs to user
        $task = Task::where('id', $taskId)
            ->where('user_id', $this->user->id)
            ->with('column.board')
            ->first();

        if (!$task) {
            return 'No se encontró la tarea o no pertenece al usuario.';
        }

        // Verify target column exists and belongs to same board
        $targetColumn = Column::where('id', $targetColumnId)
            ->where('board_id', $task->column->board_id)
            ->first();

        if (!$targetColumn) {
            return 'No se encontró la columna de destino o pertenece a otro tablero.';
        }

        // If already in target column, no need to move
        if ($task->column_id === $targetColumnId) {
            return "La tarea '{$task->title}' ya está en la columna '{$targetColumn->name}'.";
        }

        $oldColumnName = $task->column->name;

        $taskService = app(TaskService::class);
        $taskService->moveTask($task, $targetColumnId, 0); // Move to top of target column

        return "✅ Tarea '{$task->title}' movida de '{$oldColumnName}' a '{$targetColumn->name}'.";
    }
}
