<?php

namespace App\Mcp\Tools\Tags;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateTagTool extends Tool
{
    protected string $description = 'Creates a new tag for the authenticated user. Tags are global per user — they can later be attached to boards and tasks polymorphically.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Tag display name (1-100 chars)')->required(),
            'color' => $schema->string()->description('Hex color like #ef4444 (default: #94a3b8 slate)'),
            'description' => $schema->string()->description('Optional description of when to use this tag'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'description' => 'nullable|string|max:500',
        ]);

        $slug = Str::slug($request->get('name'));

        if ($slug === '') {
            return Response::text('Error: tag name produces an empty slug.');
        }

        $existing = Tag::where('user_id', $user->id)->where('slug', $slug)->first();
        if ($existing) {
            return Response::text("Tag already exists: [{$existing->id}] {$existing->name} ({$existing->color}).");
        }

        $tag = $user->tags()->create([
            'name' => $request->get('name'),
            'slug' => $slug,
            'color' => $request->get('color', '#94a3b8'),
            'description' => $request->get('description'),
        ]);

        return Response::text("Tag '{$tag->name}' created (ID: {$tag->id}, color: {$tag->color}).");
    }
}
