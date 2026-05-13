<?php

namespace App\Mcp\Tools\Cycles;

use App\Modules\Tasks\Models\Cycle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListTasksByCycleTool extends Tool
{
    protected string $description = 'Lists every task assigned to a cycle, grouped by their current column. Useful for sprint reviews.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'cycle_id' => $schema->integer()->description('Cycle ID')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate(['cycle_id' => 'required|integer']);

        $cycle = Cycle::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->with(['tasks.column', 'tasks.tags'])
            ->find($request->get('cycle_id'));

        if (! $cycle) {
            return Response::text('Error: Cycle not found or does not belong to you.');
        }

        if ($cycle->tasks->isEmpty()) {
            return Response::text("Cycle '{$cycle->name}' has no tasks.");
        }

        $grouped = $cycle->tasks->groupBy(fn ($t) => $t->column->name);

        $output = "Cycle '{$cycle->name}' ({$cycle->status}) — {$cycle->tasks->count()} tasks:\n\n";

        foreach ($grouped as $columnName => $tasks) {
            $output .= "## {$columnName} ({$tasks->count()})\n";
            foreach ($tasks as $t) {
                $priority = match ($t->priority) {
                    'high' => "\xF0\x9F\x94\xB4",
                    'medium' => "\xF0\x9F\x9F\xA1",
                    'low' => "\xF0\x9F\x9F\xA2",
                    default => "\xE2\x9A\xAA",
                };
                $output .= "   {$priority} [{$t->id}] {$t->title}";
                if ($t->due_date) {
                    $output .= " (due: {$t->due_date->format('Y-m-d')})";
                }
                if ($t->tags->isNotEmpty()) {
                    $output .= ' [' . $t->tags->pluck('name')->join(', ') . ']';
                }
                $output .= "\n";
            }
            $output .= "\n";
        }

        return Response::text($output);
    }
}
