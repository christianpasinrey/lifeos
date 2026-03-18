<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListTasksTool extends Tool
{
    protected string $description = 'Lists tasks in a board, optionally filtered by column, priority, or search term.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board (required)')->required(),
            'column_id' => $schema->integer()->description('Filter by specific column ID'),
            'priority' => $schema->string()->description('Filter by priority: low, medium, or high'),
            'search' => $schema->string()->description('Search in task title and description'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'board_id' => 'required|integer',
            'column_id' => 'nullable|integer',
            'priority' => 'nullable|in:low,medium,high',
            'search' => 'nullable|string|max:255',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $query = $board->tasks()
            ->with('customFieldValues.field', 'media')
            ->when($request->get('column_id'), fn ($q, $colId) => $q->where('column_id', $colId))
            ->when($request->get('priority'), fn ($q, $p) => $q->where('priority', $p))
            ->when($request->get('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->orderBy('sort_order');

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return Response::text("No tasks found in board '{$board->name}' with the given filters.");
        }

        $output = "Tasks in '{$board->name}':\n\n";

        foreach ($tasks as $task) {
            $priority = match ($task->priority) {
                'high' => "\xF0\x9F\x94\xB4",
                'medium' => "\xF0\x9F\x9F\xA1",
                'low' => "\xF0\x9F\x9F\xA2",
                default => "\xE2\x9A\xAA",
            };
            $output .= "{$priority} [{$task->id}] {$task->title}";
            if ($task->due_date) {
                $output .= " (due: {$task->due_date->format('Y-m-d')})";
            }
            $output .= " \xe2\x80\x94 column_id: {$task->column_id}\n";
            if ($task->customFieldValues->isNotEmpty()) {
                $cfValues = $task->customFieldValues->map(fn ($v) => "{$v->field->name}: {$v->value}")->join(', ');
                $output .= "   Fields: {$cfValues}\n";
            }
            $attachCount = $task->getMedia('task-attachments')->count();
            if ($attachCount > 0) {
                $output .= "   \xF0\x9F\x93\x8E {$attachCount} attachment(s)\n";
            }
        }

        return Response::text($output);
    }
}
