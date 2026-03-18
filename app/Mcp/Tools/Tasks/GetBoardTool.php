<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetBoardTool extends Tool
{
    protected string $description = 'Gets a specific board with all its columns and tasks. Use this to see the full state of a project.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board to retrieve')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate(['board_id' => 'required|integer']);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->with(['columns.tasks' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $output = "\xF0\x9F\x93\x8B {$board->name} (ID: {$board->id})\n";
        if ($board->description) {
            $output .= "{$board->description}\n";
        }
        $output .= "\n";

        foreach ($board->columns as $column) {
            $output .= "## {$column->name} (ID: {$column->id})\n";

            if ($column->tasks->isEmpty()) {
                $output .= "   (empty)\n\n";
                continue;
            }

            foreach ($column->tasks as $task) {
                $priority = match ($task->priority) {
                    'high' => "\xF0\x9F\x94\xB4",
                    'medium' => "\xF0\x9F\x9F\xA1",
                    'low' => "\xF0\x9F\x9F\xA2",
                    default => "\xE2\x9A\xAA",
                };

                $output .= "   {$priority} [{$task->id}] {$task->title}";
                if ($task->due_date) {
                    $output .= " (due: {$task->due_date->format('Y-m-d')})";
                }
                $output .= "\n";
                if ($task->description) {
                    $output .= "      {$task->description}\n";
                }
            }

            $output .= "\n";
        }

        return Response::text($output);
    }
}
