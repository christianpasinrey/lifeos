<?php

namespace App\Mcp\Tools\Tags;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListTagsTool extends Tool
{
    protected string $description = 'Lists all tags owned by the authenticated user. Tags are shared across boards and tasks via a polymorphic taggable pivot. Optionally filter by taggable_type=board|task to only return tags currently in use on that entity type.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'taggable_type' => $schema->string()->description('Optional filter: "board" or "task" — returns tags currently attached to at least one entity of that type.'),
            'search' => $schema->string()->description('Optional search in tag name'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'taggable_type' => 'nullable|in:board,task',
            'search' => 'nullable|string|max:255',
        ]);

        $morphMap = [
            'board' => \App\Modules\Tasks\Models\Board::class,
            'task' => \App\Modules\Tasks\Models\Task::class,
        ];

        $query = Tag::where('user_id', $user->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->get('taggable_type'), function ($q, $type) use ($morphMap) {
                $cls = $morphMap[$type] ?? null;
                if ($cls === null) {
                    return $q;
                }

                return $q->whereExists(function ($sub) use ($cls) {
                    $sub->select(\DB::raw(1))
                        ->from('taggables')
                        ->whereColumn('taggables.tag_id', 'tags.id')
                        ->where('taggables.taggable_type', $cls);
                });
            })
            ->withCount(['boards', 'tasks'])
            ->orderBy('name');

        $tags = $query->get();

        if ($tags->isEmpty()) {
            return Response::text('No tags found.');
        }

        $output = "Tags:\n\n";
        foreach ($tags as $tag) {
            $output .= "- [{$tag->id}] {$tag->name} ({$tag->color}) — boards: {$tag->boards_count}, tasks: {$tag->tasks_count}";
            if ($tag->description) {
                $output .= " — {$tag->description}";
            }
            $output .= "\n";
        }

        return Response::text($output);
    }
}
