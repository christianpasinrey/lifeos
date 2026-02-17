<?php

namespace App\Modules\Tasks\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class TaskGenerator implements Agent
{
    use Promptable;

    public function model(): string
    {
        return config('ai.providers.openai.model', env('CHATGPT_MODEL', 'gpt-4o-mini'));
    }

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Eres un asistente de productividad. Tu trabajo es descomponer una descripción
        de proyecto o tarea en subtareas concretas y accionables.

        REGLAS:
        - Responde SOLO con un JSON array válido, sin texto adicional ni markdown
        - Cada tarea es un objeto con: "title" (string), "priority" (low|medium|high)
        - Genera entre 3 y 10 tareas
        - Ordénalas de forma lógica (primero lo que se debe hacer antes)
        - Títulos concisos, máximo 80 caracteres, en español
        - Prioridad "high" para las bloqueantes o urgentes, "low" para las opcionales

        Ejemplo de respuesta:
        [{"title":"Definir requisitos del proyecto","priority":"high"},{"title":"Diseñar mockups","priority":"medium"}]
        PROMPT;
    }
}
