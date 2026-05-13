<?php

namespace App\Mcp\Tools\Tags;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteTagTool extends Tool
{
    protected string $description = 'Deletes a tag and removes it from every board and task it was attached to.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'tag_id' => $schema->integer()->description('Tag ID to delete')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate(['tag_id' => 'required|integer']);

        $tag = Tag::where('id', $request->get('tag_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $tag) {
            return Response::text('Error: Tag not found or does not belong to you.');
        }

        $name = $tag->name;
        $tag->delete(); // cascadeOnDelete on taggables cleans up

        return Response::text("Tag '{$name}' deleted.");
    }
}
