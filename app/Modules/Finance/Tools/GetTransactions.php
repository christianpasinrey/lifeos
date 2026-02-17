<?php

namespace App\Modules\Finance\Tools;

use App\Models\User;
use App\Modules\Finance\FinanceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTransactions implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene las transacciones del usuario. Puede filtrar por tipo (income/expense), categoría y rango de fechas.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('Tipo de transacción: "income" para ingresos, "expense" para gastos (opcional)'),
            'category_id' => $schema->integer()
                ->description('ID de la categoría para filtrar (opcional)'),
            'start_date' => $schema->string()
                ->description('Fecha de inicio en formato YYYY-MM-DD (opcional)'),
            'end_date' => $schema->string()
                ->description('Fecha de fin en formato YYYY-MM-DD (opcional)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(FinanceService::class);

        $transactions = $service->getTransactions(
            $this->user,
            $request->string('type'),
            $request->integer('category_id'),
            $request->string('start_date'),
            $request->string('end_date')
        );

        if ($transactions->isEmpty()) {
            return 'No se encontraron transacciones con los filtros especificados.';
        }

        $output = "📝 **Transacciones** ({$transactions->count()} encontradas):\n\n";

        foreach ($transactions as $transaction) {
            $icon = $transaction->type === 'income' ? '💰' : '💸';
            $category = $transaction->category ? " [{$transaction->category->name}]" : '';
            $output .= "{$icon} {$transaction->date->format('Y-m-d')} - {$transaction->description}{$category}\n";
            $output .= "   €" . number_format($transaction->amount, 2) . "\n";

            if ($transaction->notes) {
                $output .= "   Notas: {$transaction->notes}\n";
            }

            $output .= "\n";
        }

        return $output;
    }
}
