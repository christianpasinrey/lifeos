<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use App\Services\TagService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateBoardTool extends Tool
{
    protected string $description = 'Updates a board\'s name, description, and tags. Pass replace_tags=true to swap tags wholesale.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board to update')->required(),
            'name' => $schema->string()->description('New name for the board'),
            'description' => $schema->string()->description('New description for the board'),
            'tag_ids' => $schema->array()->description('Tag IDs (attach, or sync if replace_tags=true)'),
            'tag_names' => $schema->array()->description('Tag names — auto-created'),
            'replace_tags' => $schema->boolean()->description('If true, replace tags wholesale; otherwise additive.'),
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
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
            'replace_tags' => 'nullable|boolean',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $data = [];
        foreach (['name', 'description'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->get($field);
            }
        }

        $touchedTags = $request->has('tag_ids') || $request->has('tag_names') || $request->has('replace_tags');

        if (empty($data) && ! $touchedTags) {
            return Response::text('Error: Provide at least name, description, or tags to update.');
        }

        if (! empty($data)) {
            $board->update($data);
        }

        if ($touchedTags) {
            $service = app(TagService::class);
            $resolved = $service->resolveTagIds(
                $user,
                $request->get('tag_ids', []) ?: [],
                $request->get('tag_names', []) ?: [],
            );
            $service->applyToTaggable($board, $resolved, (bool) $request->get('replace_tags', false));
        }

        $tags = $board->fresh()->tags->pluck('name')->join(', ');
        $tagsLine = $tags ? " [tags: {$tags}]" : '';

        return Response::text("Board '{$board->name}' updated (ID: {$board->id}){$tagsLine}.");
    }
}
