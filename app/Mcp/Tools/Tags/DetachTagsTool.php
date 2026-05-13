<?php

namespace App\Mcp\Tools\Tags;

use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DetachTagsTool extends Tool
{
    protected string $description = 'Detaches one or more tags from a board or task. Pass tag_ids to remove specific tags, or all=true to clear every tag from the target.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'target_type' => $schema->string()->description('"board" or "task"')->required(),
            'target_id' => $schema->integer()->description('ID of the board or task')->required(),
            'tag_ids' => $schema->array()->description('Tag IDs to detach (ignored if all=true)'),
            'all' => $schema->boolean()->description('Remove every tag from the target'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'target_type' => 'required|in:board,task',
            'target_id' => 'required|integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'all' => 'nullable|boolean',
        ]);

        $type = $request->get('target_type');
        $target = $type === 'board'
            ? Board::where('id', $request->get('target_id'))->where('user_id', $user->id)->first()
            : Task::where('id', $request->get('target_id'))->where('user_id', $user->id)->first();

        if (! $target) {
            return Response::text("Error: {$type} not found or does not belong to you.");
        }

        if ($request->get('all')) {
            $target->tags()->detach();
            $label = $type === 'board' ? "board '{$target->name}'" : "task '{$target->title}'";

            return Response::text("All tags removed from {$label}.");
        }

        $ids = $request->get('tag_ids', []);
        if (empty($ids)) {
            return Response::text('Error: pass tag_ids or set all=true.');
        }

        $target->tags()->detach($ids);
        $remaining = $target->fresh()->tags->pluck('name')->join(', ') ?: '(none)';
        $label = $type === 'board' ? "board '{$target->name}'" : "task '{$target->title}'";

        return Response::text("Tags after detach on {$label}: {$remaining}");
    }
}
