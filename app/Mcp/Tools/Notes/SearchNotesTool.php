<?php

namespace App\Mcp\Tools\Notes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchNotesTool extends Tool
{
    protected string $description = 'Searches notes by title or content using full-text search.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string('Search query text'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (!$user->hasModule('notes')) {
            return Response::text('Error: Notes module is not active.');
        }

        $q = $request->input('query', '');
        if (empty($q)) {
            return Response::text('Error: query is required.');
        }

        $notes = $user->notes()
            ->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$q . '*'])
            ->select('id', 'title', 'slug', 'folder_id', 'updated_at')
            ->limit(15)
            ->get();

        if ($notes->isEmpty()) {
            return Response::text("No notes found for \"{$q}\".");
        }

        $output = "Notes matching \"{$q}\":\n\n";
        foreach ($notes as $note) {
            $output .= "- [{$note->id}] {$note->title} (slug: {$note->slug})\n";
        }

        return Response::text($output);
    }
}
