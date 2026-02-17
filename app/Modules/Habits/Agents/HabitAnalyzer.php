<?php

namespace App\Modules\Habits\Agents;

use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class HabitAnalyzer implements Agent
{
    use Promptable;

    public function __construct(
        private User $user,
        private string $habitsDataJson,
        private string $goals,
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
        return <<<PROMPT
        Eres un coach experto en formación de hábitos y productividad personal.

        Hábitos actuales del usuario (datos de los últimos 30 días):
        ```json
        {$this->habitsDataJson}
        ```

        Propósitos/objetivos del usuario:
        "{$this->goals}"

        Analiza los datos actuales Y los propósitos del usuario. Genera un informe estructurado en formato JSON con esta estructura exacta:
        {
            "summary": "Resumen general en 1-2 frases conectando estado actual con propósitos",
            "score": <número 0-100 representando salud general de hábitos>,
            "strengths": ["punto fuerte 1", "punto fuerte 2"],
            "risks": [
                {"habit": "nombre del hábito", "issue": "descripción del problema", "suggestion": "consejo concreto"}
            ],
            "weak_days": [
                {"day": "Lunes", "rate": 45, "tip": "consejo para mejorar ese día"}
            ],
            "recommendations": ["recomendación accionable 1", "recomendación accionable 2", "recomendación accionable 3"],
            "suggested_habits": [
                {
                    "name": "Nombre del hábito sugerido",
                    "description": "Por qué este hábito ayuda a tus propósitos",
                    "type": "boolean",
                    "unit": null,
                    "target_value": null,
                    "frequency": "daily",
                    "target_days": null,
                    "routine": "morning",
                    "color": "#6366f1",
                    "replaces": null
                }
            ]
        }

        Reglas:
        - Responde EXCLUSIVAMENTE con JSON válido, sin texto ni markdown
        - "strengths": máximo 3 puntos fuertes basados en datos reales
        - "risks": solo hábitos con tasa < 60% en los últimos 30 días (puede ser vacío)
        - "weak_days": solo días con promedio < 60%, máximo 3 (puede ser vacío)
        - "recommendations": exactamente 3 recomendaciones concretas y accionables
        - "suggested_habits": entre 2 y 5 hábitos sugeridos alineados a los propósitos del usuario
          - "type": "boolean" o "numeric"
          - "unit" y "target_value": solo si type es "numeric"
          - "frequency": "daily", "weekly" o "custom"
          - "target_days": null para daily, o array de ["mon","tue","wed","thu","fri","sat","sun"] para weekly/custom
          - "routine": "morning", "afternoon", "evening" o "anytime"
          - "color": un color hex que tenga sentido con el hábito
          - "replaces": nombre exacto de un hábito existente que este sustituye, o null si es nuevo
          - No sugieras hábitos que el usuario ya tenga (a menos que sea para reemplazarlos con una versión mejorada)
        - Todo en español
        - Sé específico: menciona nombres, números y días concretos
        - No inventes datos de los hábitos existentes, usa solo lo proporcionado
        PROMPT;
    }
}
