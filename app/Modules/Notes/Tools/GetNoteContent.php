<?php

namespace App\Modules\Notes\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetNoteContent implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene el contenido completo de una nota por su ID o slug.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'note_id' => $schema->integer('ID de la nota (opcional si se proporciona slug)')->nullable(),
            'slug' => $schema->string('Slug de la nota (opcional si se proporciona ID)')->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $query = $this->user->notes();

        if ($request->input('note_id')) {
            $query->where('id', $request->input('note_id'));
        } elseif ($request->input('slug')) {
            $query->where('slug', $request->input('slug'));
        } else {
            return 'Debes proporcionar note_id o slug.';
        }

        $note = $query->first();

        if (!$note) {
            return 'Nota no encontrada.';
        }

        $output = "# {$note->title}\n\n";
        $output .= "Slug: {$note->slug} | ID: {$note->id}\n";
        $output .= "Carpeta: " . ($note->folder?->name ?? 'Raíz') . "\n";
        $output .= "Actualizada: {$note->updated_at}\n\n";
        $output .= "---\n\n";
        $output .= $note->content ?? '(sin contenido)';

        return $output;
    }
}
