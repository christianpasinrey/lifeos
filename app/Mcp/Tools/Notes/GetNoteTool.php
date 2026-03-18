<?php

namespace App\Mcp\Tools\Notes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetNoteTool extends Tool
{
    protected string $description = 'Gets the full content of a note by ID, including tags and links.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'note_id' => $schema->integer('The ID of the note to retrieve'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (!$user->hasModule('notes')) {
            return Response::text('Error: Notes module is not active.');
        }

        $note = $user->notes()->with('folder', 'tags')->find($request->input('note_id'));

        if (!$note) {
            return Response::text('Note not found.');
        }

        $output = "# {$note->title}\n\n";
        $output .= "ID: {$note->id} | Slug: {$note->slug}\n";
        $output .= "Folder: " . ($note->folder?->name ?? 'Root') . "\n";
        $output .= "Updated: {$note->updated_at}\n";

        $tags = $note->tags->pluck('full_path')->join(', ');
        if ($tags) $output .= "Tags: {$tags}\n";

        $output .= "\n---\n\n";
        $output .= $note->content ?? '(empty)';

        return Response::text($output);
    }
}
