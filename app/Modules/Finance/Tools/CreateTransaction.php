<?php

namespace App\Modules\Finance\Tools;

use App\Models\User;
use App\Modules\Finance\FinanceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTransaction implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Registra una nueva transacción financiera (ingreso o gasto) para el usuario.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('Tipo de transacción: "income" para ingreso, "expense" para gasto')
                ->required(),
            'amount' => $schema->number()
                ->description('Cantidad en euros (debe ser mayor a 0)')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción breve de la transacción')
                ->required(),
            'notes' => $schema->string()
                ->description('Notas adicionales sobre la transacción (opcional)'),
            'category_id' => $schema->integer()
                ->description('ID de la categoría de la transacción (opcional)'),
            'date' => $schema->string()
                ->description('Fecha de la transacción en formato YYYY-MM-DD (opcional, por defecto: hoy)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(FinanceService::class);

        $data = [
            'type' => $request['type'],
            'amount' => (float) $request['amount'],
            'description' => $request['description'],
            'notes' => $request['notes'] ?? null,
            'category_id' => !empty($request['category_id']) ? (int) $request['category_id'] : null,
            'date' => $request['date'] ?? null,
        ];

        $transaction = $service->createTransaction($this->user, $data);

        $icon = $transaction->type === 'income' ? '💰' : '💸';
        $category = $transaction->category ? " en categoría '{$transaction->category->name}'" : '';

        return "{$icon} Transacción registrada: {$transaction->description}{$category} - €" .
               number_format($transaction->amount, 2) . " el {$transaction->date->format('Y-m-d')}";
    }
}
