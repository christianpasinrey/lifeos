<?php

namespace App\Mcp\Tools\Tags;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateTagTool extends Tool
{
    protected string $description = 'Updates a tag\'s name, color, or description. Renaming regenerates the slug; rejects if the new slug clashes with another user tag.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'tag_id' => $schema->integer()->description('Tag ID to update')->required(),
            'name' => $schema->string()->description('New name'),
            'color' => $schema->string()->description('New hex color'),
            'description' => $schema->string()->description('New description (pass empty string to clear)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'tag_id' => 'required|integer',
            'name' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'description' => 'nullable|string|max:500',
        ]);

        $tag = Tag::where('id', $request->get('tag_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $tag) {
            return Response::text('Error: Tag not found or does not belong to you.');
        }

        $data = [];
        if ($request->has('name')) {
            $name = $request->get('name');
            $slug = Str::slug($name);
            if ($slug === '') {
                return Response::text('Error: empty slug.');
            }
            $clash = Tag::where('user_id', $user->id)
                ->where('slug', $slug)
                ->where('id', '!=', $tag->id)
                ->exists();
            if ($clash) {
                return Response::text("Error: another tag already uses slug '{$slug}'.");
            }
            $data['name'] = $name;
            $data['slug'] = $slug;
        }
        if ($request->has('color')) {
            $data['color'] = $request->get('color');
        }
        if ($request->has('description')) {
            $data['description'] = $request->get('description') ?: null;
        }

        if (empty($data)) {
            return Response::text('Error: nothing to update.');
        }

        $tag->update($data);

        return Response::text("Tag '{$tag->name}' updated (ID: {$tag->id}).");
    }
}
