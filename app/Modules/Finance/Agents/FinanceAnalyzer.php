<?php

namespace App\Modules\Finance\Agents;

use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class FinanceAnalyzer implements Agent
{
    use Promptable;

    public function __construct(
        private User $user,
        private string $financialDataJson,
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
        Eres un analista financiero personal experto. Analizas los movimientos financieros del usuario y proporcionas consejos accionables.

        Datos financieros del usuario:
        ```json
        {$this->financialDataJson}
        ```

        Analiza todos los datos proporcionados y genera un informe estructurado en formato JSON con esta estructura exacta:
        {
            "summary": "Resumen general en 2-3 frases sobre la salud financiera del periodo",
            "score": <número 0-100 representando salud financiera>,
            "highlights": [
                {"type": "positive", "text": "algo positivo"},
                {"type": "warning", "text": "algo a vigilar"},
                {"type": "danger", "text": "algo preocupante"}
            ],
            "category_analysis": [
                {
                    "category": "Nombre categoría",
                    "amount": 0.00,
                    "percentage": 0,
                    "trend": "up|down|stable",
                    "advice": "Consejo específico para esta categoría"
                }
            ],
            "recommendations": ["consejo accionable 1", "consejo accionable 2", "consejo accionable 3"],
            "suggested_actions": [
                {
                    "type": "habit",
                    "title": "Nombre del hábito sugerido",
                    "description": "Por qué este hábito ayuda a tus finanzas"
                },
                {
                    "type": "task",
                    "title": "Título de la tarea",
                    "description": "Qué hacer concretamente"
                }
            ]
        }

        Reglas:
        - Responde EXCLUSIVAMENTE con JSON válido, sin texto ni markdown
        - "highlights": entre 2 y 6 items. Tipos válidos: "positive", "warning", "danger"
        - "category_analysis": solo categorías con gastos significativos. "trend" compara con periodo anterior si hay datos, si no "stable". "percentage" es % sobre el total de gastos
        - "recommendations": exactamente 3 recomendaciones concretas y accionables basadas en los datos reales
        - "suggested_actions": entre 1 y 4 acciones. Pueden ser hábitos financieros (type: "habit") o tareas concretas (type: "task")
          - Para "habit": sugiere hábitos como "Revisar gastos diarios", "Ahorrar X% del ingreso", etc.
          - Para "task": sugiere tareas puntuales como "Renegociar suscripción X", "Crear presupuesto para Y", etc.
        - Todo en español
        - Sé específico: menciona cantidades, categorías y porcentajes concretos de los datos
        - No inventes datos, usa solo lo proporcionado
        - Si hay periodo anterior, compara tendencias
        - Si hay presupuestos, evalúa su cumplimiento
        PROMPT;
    }
}
