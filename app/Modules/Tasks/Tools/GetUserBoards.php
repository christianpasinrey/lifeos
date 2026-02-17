<?php

namespace App\Modules\Tasks\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetUserBoards implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene los tableros Kanban del usuario con sus columnas y el número de tareas en cada uno.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $boards = $this->user->boards()
            ->with(['columns' => function ($query) {
                $query->withCount('tasks');
            }])
            ->get();

        if ($boards->isEmpty()) {
            return 'El usuario no tiene tableros todavía.';
        }

        $output = "Tableros del usuario:\n\n";

        foreach ($boards as $board) {
            $output .= "📋 **{$board->name}** (ID: {$board->id})\n";
            if ($board->description) {
                $output .= "   Descripción: {$board->description}\n";
            }
            $output .= "   Columnas:\n";

            foreach ($board->columns as $column) {
                $tasksCount = $column->tasks_count ?? 0;
                $output .= "   - {$column->name} (ID: {$column->id}) - {$tasksCount} tareas\n";
            }

            $output .= "\n";
        }

        return $output;
    }
}
