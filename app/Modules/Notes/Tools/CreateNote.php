<?php

namespace App\Modules\Notes\Tools;

use App\Models\User;
use App\Modules\Notes\NoteService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateNote implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Crea una nueva nota con título y contenido markdown.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string('Título de la nota'),
            'content' => $schema->string('Contenido en formato Markdown')->nullable(),
            'folder_id' => $schema->integer('ID de la carpeta (opcional)')->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(NoteService::class);

        $note = $service->createNote($this->user, [
            'title' => $request->input('title'),
            'content' => $request->input('content', ''),
            'folder_id' => $request->input('folder_id'),
        ]);

        return "Nota creada: **{$note->title}** (slug: {$note->slug}, ID: {$note->id})";
    }
}
