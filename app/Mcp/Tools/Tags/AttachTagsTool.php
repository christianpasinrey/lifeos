<?php

namespace App\Mcp\Tools\Tags;

use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Task;
use App\Services\TagService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AttachTagsTool extends Tool
{
    protected string $description = 'Attaches one or more tags to a board or task. Accepts either tag_ids (existing) or tag_names (auto-created). When replace=true, the target\'s existing tags are first detached.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'target_type' => $schema->string()->description('What to tag: "board" or "task"')->required(),
            'target_id' => $schema->integer()->description('ID of the board or task')->required(),
            'tag_ids' => $schema->array()->description('Array of existing tag IDs'),
            'tag_names' => $schema->array()->description('Array of tag names — created if they do not exist'),
            'replace' => $schema->boolean()->description('If true, replace all existing tags. Default: false (additive).'),
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
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
            'replace' => 'nullable|boolean',
        ]);

        $target = $this->loadTarget($request->get('target_type'), (int) $request->get('target_id'), $user->id);

        if ($target === null) {
            return Response::text("Error: {$request->get('target_type')} not found or does not belong to you.");
        }

        $ids = $request->get('tag_ids', []) ?: [];
        $names = $request->get('tag_names', []) ?: [];

        if (empty($ids) && empty($names)) {
            return Response::text('Error: provide at least tag_ids or tag_names.');
        }

        /** @var TagService $service */
        $service = app(TagService::class);

        $tagIds = $service->resolveTagIds($user, $ids, $names);

        if (empty($tagIds)) {
            return Response::text('Error: no valid tags resolved.');
        }

        $service->applyToTaggable($target, $tagIds, (bool) $request->get('replace', false));

        $current = $target->fresh()->tags->pluck('name')->join(', ');
        $label = $request->get('target_type') === 'board' ? "board '{$target->name}'" : "task '{$target->title}'";

        return Response::text("Tags on {$label}: {$current}");
    }

    private function loadTarget(string $type, int $id, int $userId)
    {
        if ($type === 'board') {
            return Board::where('id', $id)->where('user_id', $userId)->first();
        }

        return Task::where('id', $id)->where('user_id', $userId)->first();
    }
}
