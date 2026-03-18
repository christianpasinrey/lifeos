<?php

namespace App\Modules\Notes\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchNotes implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Busca notas del usuario por título o contenido. Devuelve título, slug y extracto.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string('Texto a buscar en las notas'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $q = $request->input('query', '');

        $notes = $this->user->notes()
            ->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$q . '*'])
            ->select('id', 'title', 'slug')
            ->limit(10)
            ->get();

        if ($notes->isEmpty()) {
            return "No se encontraron notas para \"{$q}\".";
        }

        $output = "Notas encontradas:\n\n";
        foreach ($notes as $note) {
            $output .= "- **{$note->title}** (slug: {$note->slug}, ID: {$note->id})\n";
        }

        return $output;
    }
}
