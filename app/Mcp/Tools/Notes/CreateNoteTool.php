<?php

namespace App\Mcp\Tools\Notes;

use App\Modules\Notes\NoteService;
use App\Modules\Notes\Services\LinkExtractorService;
use App\Modules\Notes\Services\TagParserService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateNoteTool extends Tool
{
    protected string $description = 'Creates a new note with title and markdown content.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string('Title for the new note'),
            'content' => $schema->string('Markdown content (optional)')->nullable(),
            'folder_id' => $schema->integer('Folder ID to create the note in (optional)')->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (!$user->hasModule('notes')) {
            return Response::text('Error: Notes module is not active.');
        }

        $service = app(NoteService::class);

        $note = $service->createNote($user, [
            'title' => $request->input('title'),
            'content' => $request->input('content', ''),
            'folder_id' => $request->input('folder_id'),
        ]);

        app(LinkExtractorService::class)->extract($note);
        app(TagParserService::class)->parse($note);

        return Response::text("Note created: '{$note->title}' (ID: {$note->id}, slug: {$note->slug})");
    }
}
