<?php

namespace App\Modules\Ai\Agents;

use App\Models\User;
use App\Modules\Ai\AiCoachRegistry;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class AiCoach implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(private User $user) {}

    public function model(): string
    {
        return config('ai.openai_model', env('CHATGPT_MODEL', 'gpt-4o-mini'));
    }

    public function instructions(): Stringable|string
    {
        $basePrompt = <<<'PROMPT'
        Eres un coach personal y asistente de productividad. Tu nombre es Coach.
        Tienes acceso a los datos reales del usuario según los módulos activos.

        Reglas generales:
        - Responde SIEMPRE en español
        - Sé conciso, empático y motivador
        - Usa los datos reales del usuario para personalizar tus respuestas
        - No inventes datos, usa siempre las herramientas disponibles para consultar información
        - Cuando el usuario pida crear o modificar algo, usa las herramientas directamente
        PROMPT;

        $registry = app(AiCoachRegistry::class);
        $specializedInstructions = $registry->composeInstructions($this->user);

        if ($specializedInstructions) {
            return $basePrompt . "\n\n" . $specializedInstructions;
        }

        return $basePrompt;
    }

    public function tools(): iterable
    {
        $registry = app(AiCoachRegistry::class);
        return $registry->composeTools($this->user);
    }
}
