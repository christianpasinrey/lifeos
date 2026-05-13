<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\TaskService;
use App\Services\TagService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateBoardTool extends Tool
{
    protected string $description = 'Creates a new Kanban board. Automatically creates 3 default columns: "Por hacer", "En curso", "Hecho". Optional tags can be attached at creation.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Name for the new board')->required(),
            'description' => $schema->string()->description('Optional description for the board'),
            'tag_ids' => $schema->array()->description('Optional array of existing tag IDs to attach to the board'),
            'tag_names' => $schema->array()->description('Optional array of tag names — auto-created if needed'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
        ]);

        try {
            $board = app(TaskService::class)->createBoard($user, [
                'name' => $request->get('name'),
                'description' => $request->get('description'),
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return Response::text("Error: {$e->getMessage()}");
        }

        $tagIds = $request->get('tag_ids', []) ?: [];
        $tagNames = $request->get('tag_names', []) ?: [];
        if (! empty($tagIds) || ! empty($tagNames)) {
            $service = app(TagService::class);
            $resolved = $service->resolveTagIds($user, $tagIds, $tagNames);
            if (! empty($resolved)) {
                $service->applyToTaggable($board, $resolved);
            }
        }

        $columns = $board->columns->pluck('name')->implode(', ');
        $tags = $board->fresh()->tags->pluck('name')->join(', ');
        $tagsLine = $tags ? " [tags: {$tags}]" : '';

        return Response::text("Board '{$board->name}' created (ID: {$board->id}) with columns: {$columns}{$tagsLine}.");
    }
}
