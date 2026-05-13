<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetBoardTool extends Tool
{
    protected string $description = 'Gets a specific board with all its columns, tasks, tags, custom fields, and cycles. Use this to see the full state of a project.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board to retrieve')->required(),
            'include_body_html' => $schema->boolean()->description('If true, include the raw body_html for each task. Defaults to false (only a marker).'),
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
            'include_body_html' => 'nullable|boolean',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->with([
                'tags',
                'customFields',
                'cycles' => fn ($q) => $q->withCount('tasks'),
                'columns.tasks' => fn ($q) => $q->orderBy('sort_order'),
                'columns.tasks.customFieldValues.field',
                'columns.tasks.tags',
                'columns.tasks.cycle',
            ])
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $includeBody = (bool) $request->get('include_body_html', false);

        $output = "\xF0\x9F\x93\x8B {$board->name} (ID: {$board->id})\n";
        if ($board->description) {
            $output .= "{$board->description}\n";
        }
        if ($board->tags->isNotEmpty()) {
            $output .= "Tags: " . $board->tags->pluck('name')->join(', ') . "\n";
        }
        $output .= "\n";

        if ($board->customFields->isNotEmpty()) {
            $output .= "## Custom Fields\n";
            foreach ($board->customFields as $f) {
                $output .= "- {$f->name} ({$f->type})" . ($f->required ? ' [required]' : '') . "\n";
            }
            $output .= "\n";
        }

        if ($board->cycles->isNotEmpty()) {
            $output .= "## Cycles ({$board->cycles->count()})\n";
            foreach ($board->cycles as $c) {
                $output .= "- [{$c->id}] {$c->name} ({$c->status}) — {$c->tasks_count} tasks";
                if ($c->starts_on || $c->ends_on) {
                    $output .= ' — ' . ($c->starts_on?->format('Y-m-d') ?? '?') . ' → ' . ($c->ends_on?->format('Y-m-d') ?? '?');
                }
                $output .= "\n";
            }
            $output .= "\n";
        }

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
                if ($task->cycle) {
                    $output .= " <cycle:{$task->cycle->name}>";
                }
                $output .= "\n";
                if ($task->description) {
                    $output .= "      {$task->description}\n";
                }
                if ($task->body_html) {
                    if ($includeBody) {
                        $output .= "      [body_html]:\n      " . str_replace("\n", "\n      ", $task->body_html) . "\n";
                    } else {
                        $bytes = strlen($task->body_html);
                        $output .= "      \xF0\x9F\x93\x84 rich body ({$bytes} bytes) — pass include_body_html=true to inline it\n";
                    }
                }
                if ($task->tags->isNotEmpty()) {
                    $output .= '      Tags: ' . $task->tags->pluck('name')->join(', ') . "\n";
                }
                if ($task->customFieldValues->isNotEmpty()) {
                    foreach ($task->customFieldValues as $fv) {
                        $output .= "      {$fv->field->name}: {$fv->value}\n";
                    }
                }
            }

            $output .= "\n";
        }

        return Response::text($output);
    }
}
