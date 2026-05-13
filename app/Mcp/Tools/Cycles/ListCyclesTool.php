<?php

namespace App\Mcp\Tools\Cycles;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListCyclesTool extends Tool
{
    protected string $description = 'Lists cycles for a board. Cycles group tasks (sprints, milestones, releases) and can be filtered by status (planned / active / completed).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('Board ID')->required(),
            'status' => $schema->string()->description('Filter by status: planned, active, completed'),
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
            'status' => 'nullable|in:planned,active,completed',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $cycles = $board->cycles()
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->withCount('tasks')
            ->get();

        if ($cycles->isEmpty()) {
            return Response::text("No cycles on board '{$board->name}'.");
        }

        $output = "Cycles on '{$board->name}':\n\n";
        foreach ($cycles as $c) {
            $output .= "- [{$c->id}] {$c->name} ({$c->status})";
            if ($c->starts_on || $c->ends_on) {
                $output .= ' — ' . ($c->starts_on?->format('Y-m-d') ?? '?') . ' → ' . ($c->ends_on?->format('Y-m-d') ?? '?');
            }
            $output .= " — {$c->tasks_count} tasks\n";
            if ($c->description) {
                $output .= "   {$c->description}\n";
            }
        }

        return Response::text($output);
    }
}
