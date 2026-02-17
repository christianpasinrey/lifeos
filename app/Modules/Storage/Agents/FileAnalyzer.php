<?php

namespace App\Modules\Storage\Agents;

use App\Models\User;
use App\Modules\Ai\AiCoachRegistry;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class FileAnalyzer implements Agent
{
    use Promptable;

    public function __construct(
        private User $user,
        private string $fileName,
        private string $fileContent,
    ) {}

    public function model(): string
    {
        return config('ai.providers.openai.model', env('CHATGPT_MODEL', 'gpt-4o-mini'));
    }

    public function timeout(): int
    {
        return 180;
    }

    public function instructions(): Stringable|string
    {
        $base = <<<PROMPT
        Eres un asistente experto en analizar documentos. El usuario te ha compartido el contenido del archivo "{$this->fileName}".

        Contenido del archivo:
        ```
        {$this->fileContent}
        ```

        Reglas:
        - Responde SIEMPRE en español
        - Sé conciso y directo
        - Basa tu análisis exclusivamente en el contenido real del archivo
        - NUNCA ejecutes acciones automáticamente
        - PROPÓN lo que harías: lista los elementos que crearías con todos los detalles (descripción, cantidades, fechas, categorías, etc.)
        - El usuario decidirá si quiere proceder o no
        PROMPT;

        // Inject finance categories if module is active
        if ($this->user->hasModule('finance')) {
            $categories = $this->user->categories()->get(['id', 'name', 'type', 'color']);
            if ($categories->isNotEmpty()) {
                $catList = $categories->map(fn ($c) => "- [{$c->id}] {$c->name} ({$c->type})")->join("\n");
                $base .= "\n\nCategorías financieras del usuario:\n{$catList}\n";
                $base .= "Cuando propongas transacciones, sugiere la categoría más adecuada de esta lista. Si ninguna encaja, propón crear una nueva.";
            }
        }

        return $base;
    }
}
