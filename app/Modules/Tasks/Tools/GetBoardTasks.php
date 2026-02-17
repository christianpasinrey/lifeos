<?php

namespace App\Modules\Tasks\Tools;

use App\Models\User;
use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetBoardTasks implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene todas las tareas de un tablero específico, organizadas por columnas, con sus prioridades y fechas de vencimiento.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()
                ->description('ID del tablero del que obtener las tareas')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $boardId = $request->integer('board_id');

        $board = Board::where('id', $boardId)
            ->where('user_id', $this->user->id)
            ->with(['columns.tasks' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->first();

        if (!$board) {
            return 'No se encontró el tablero o no pertenece al usuario.';
        }

        $output = "📋 **{$board->name}**\n\n";

        foreach ($board->columns as $column) {
            $output .= "## {$column->name}\n";

            if ($column->tasks->isEmpty()) {
                $output .= "   (vacía)\n\n";
                continue;
            }

            foreach ($column->tasks as $task) {
                $priority = match($task->priority) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪',
                };

                $output .= "   {$priority} [{$task->id}] {$task->title}";

                if ($task->due_date) {
                    $output .= " (vence: {$task->due_date->format('Y-m-d')})";
                }

                if ($task->description) {
                    $output .= "\n      {$task->description}";
                }

                $output .= "\n";
            }

            $output .= "\n";
        }

        return $output;
    }
}
