<?php

namespace App\Modules\Notes;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;

class NotesAiSpecialization implements AiSpecialization
{
    public function moduleSlug(): string
    {
        return 'notes';
    }

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        ## Capacidades de Notas (Knowledge Management)

        Puedes ayudar al usuario a gestionar su base de conocimiento:
        - Buscar notas por título o contenido
        - Crear nuevas notas en carpetas específicas
        - Ver el contenido de una nota
        - Consultar los enlaces y backlinks de una nota

        Las notas usan formato Markdown con soporte para wiki-links [[nota]], tags #tag, y embeds ![[nota]].
        INSTRUCTIONS;
    }

    public function tools(User $user): array
    {
        // Tools will be implemented in T8
        return [];
    }
}
