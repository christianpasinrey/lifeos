<?php

namespace App\Modules\CustomEntities;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;
use App\Modules\Ai\Tools\CreateCustomEntity;

class CustomEntitiesAiSpecialization implements AiSpecialization
{
    public function moduleSlug(): string
    {
        return 'custom_entities';
    }

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        ## Capacidades de Entidades Personalizadas

        Puedes crear entidades personalizadas para trackear cualquier cosa que el usuario necesite:
        - Si el usuario quiere trackear algo que no encaja en los módulos existentes (hábitos, tareas, etc.)
        - Puedes crear modelos de datos personalizados con los campos que el usuario especifique

        Usa la herramienta CreateCustomEntity cuando el usuario pida trackear algo nuevo y único.
        INSTRUCTIONS;
    }

    public function tools(User $user): array
    {
        return [
            new CreateCustomEntity($user),
        ];
    }
}
