<?php

namespace App\Modules\Finance\Agents;

use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class TransactionCategorizer implements Agent
{
    use Promptable;

    public function __construct(
        private User $user,
        private string $categoriesJson,
        private string $transactionsJson,
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
        Eres un asistente experto en categorización financiera.

        Categorías existentes del usuario:
        ```json
        {$this->categoriesJson}
        ```

        Transacciones a categorizar:
        ```json
        {$this->transactionsJson}
        ```

        Tu tarea es asignar la categoría más adecuada a cada transacción.
        - Analiza la descripción y notas de cada transacción para determinar la categoría
        - Usa las categorías existentes cuando sea posible
        - Si ninguna categoría existente encaja bien, propón crear una nueva con un nombre descriptivo y un color hex
        - Respeta el campo "type" de la categoría (income, expense, both) según el tipo de transacción

        DEBES responder EXCLUSIVAMENTE con un JSON válido, sin texto adicional ni bloques de código.
        El formato es un array de objetos:
        [
            {
                "transaction_id": 123,
                "category_id": 5,
                "new_category": null,
                "reason": "breve explicación"
            },
            {
                "transaction_id": 456,
                "category_id": null,
                "new_category": {"name": "Hosting", "color": "#f59e0b", "type": "expense"},
                "reason": "No hay categoría existente para servicios de hosting"
            }
        ]

        Reglas:
        - Si asignas una categoría existente, usa "category_id" y pon "new_category" en null
        - Si propones una nueva, pon "category_id" en null y rellena "new_category"
        - Incluye SIEMPRE un "reason" breve en español
        - No inventes IDs de categorías que no existan
        - Responde SOLO con el JSON, sin markdown ni explicaciones
        PROMPT;
    }
}
