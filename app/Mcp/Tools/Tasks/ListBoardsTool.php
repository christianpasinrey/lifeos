<?php

namespace App\Mcp\Tools\Tasks;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListBoardsTool extends Tool
{
    protected string $description = 'Lists all Kanban boards for the authenticated user, with column count and task count per board.';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $boards = $user->boards()
            ->with(['columns' => fn ($q) => $q->withCount('tasks')])
            ->orderBy('sort_order')
            ->get();

        if ($boards->isEmpty()) {
            return Response::text('No boards found. Use create-board-tool to create one.');
        }

        $output = "Boards:\n\n";

        foreach ($boards as $board) {
            $totalTasks = $board->columns->sum('tasks_count');
            $output .= "\xF0\x9F\x93\x8B {$board->name} (ID: {$board->id}) \xe2\x80\x94 {$board->columns->count()} columns, {$totalTasks} tasks\n";
            if ($board->description) {
                $output .= "   {$board->description}\n";
            }
            foreach ($board->columns as $col) {
                $output .= "   - {$col->name} (ID: {$col->id}): {$col->tasks_count} tasks\n";
            }
            $output .= "\n";
        }

        return Response::text($output);
    }
}
