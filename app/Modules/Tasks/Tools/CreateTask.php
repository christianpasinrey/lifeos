<?php

namespace App\Modules\Tasks\Tools;

use App\Models\User;
use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTask implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Crea una nueva tarea en una columna específica de un tablero.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'column_id' => $schema->integer()
                ->description('ID de la columna donde crear la tarea')
                ->required(),
            'title' => $schema->string()
                ->description('Título de la tarea')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción detallada de la tarea (opcional)'),
            'priority' => $schema->string()
                ->description('Prioridad de la tarea: "low", "medium" o "high"'),
            'due_date' => $schema->string()
                ->description('Fecha de vencimiento en formato YYYY-MM-DD (opcional)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $columnId = $request->integer('column_id');

        // Verify column exists and belongs to user's board
        $column = Column::whereHas('board', function ($query) {
            $query->where('user_id', $this->user->id);
        })->find($columnId);

        if (!$column) {
            return 'No se encontró la columna o no pertenece a un tablero del usuario.';
        }

        $data = [
            'title' => $request->string('title'),
            'description' => $request->string('description'),
            'priority' => $request->string('priority', 'medium'),
        ];

        if ($request->has('due_date')) {
            $data['due_date'] = $request->string('due_date');
        }

        $taskService = app(TaskService::class);
        $task = $taskService->createTask($column, $this->user, $data);

        return "✅ Tarea '{$task->title}' creada exitosamente en la columna '{$column->name}' (ID: {$task->id}).";
    }
}
