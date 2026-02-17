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
        return 'Registra una nueva transacción financiera (ingreso o gasto) para el usuario. Soporta líneas de detalle con impuestos para facturas y nóminas.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('Tipo de transacción: "income" para ingreso, "expense" para gasto')
                ->required(),
            'amount' => $schema->number()
                ->description('Cantidad en euros (obligatorio si no hay líneas; se calcula automáticamente cuando hay líneas)'),
            'description' => $schema->string()
                ->description('Descripción breve de la transacción')
                ->required(),
            'notes' => $schema->string()
                ->description('Notas adicionales sobre la transacción (opcional)'),
            'category_id' => $schema->integer()
                ->description('ID de la categoría de la transacción (opcional)'),
            'account_id' => $schema->integer()
                ->description('ID de la cuenta financiera (opcional)'),
            'date' => $schema->string()
                ->description('Fecha de la transacción en formato YYYY-MM-DD (opcional, por defecto: hoy)'),
            'lines' => $schema->array()
                ->description('Líneas de detalle de la transacción (para facturas, nóminas, etc.). Si se proporcionan, el amount se calcula automáticamente.')
                ->items($schema->object([
                    'concept' => $schema->string()
                        ->description('Concepto o descripción de la línea')
                        ->required(),
                    'quantity' => $schema->number()
                        ->description('Cantidad (por defecto: 1)'),
                    'unit_price' => $schema->number()
                        ->description('Precio unitario en euros. Positivo para ingresos/devengos, negativo para deducciones.')
                        ->required(),
                    'taxes' => $schema->array()
                        ->description('Impuestos aplicables a esta línea (usar GetTaxes para obtener los IDs)')
                        ->items($schema->object([
                            'tax_id' => $schema->integer()
                                ->description('ID del impuesto')
                                ->required(),
                            'tax_rate_id' => $schema->integer()
                                ->description('ID del tipo impositivo')
                                ->required(),
                        ])),
                ])),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(FinanceService::class);

        $data = [
            'type' => $request['type'],
            'amount' => isset($request['amount']) ? (float) $request['amount'] : 0,
            'description' => $request['description'],
            'notes' => $request['notes'] ?? null,
            'category_id' => !empty($request['category_id']) ? (int) $request['category_id'] : null,
            'account_id' => !empty($request['account_id']) ? (int) $request['account_id'] : null,
            'date' => $request['date'] ?? null,
        ];

        if (!empty($request['lines'])) {
            $data['lines'] = array_map(function ($line) {
                $mapped = [
                    'concept' => $line['concept'],
                    'quantity' => $line['quantity'] ?? 1,
                    'unit_price' => (float) $line['unit_price'],
                ];

                if (!empty($line['taxes'])) {
                    $mapped['taxes'] = array_map(function ($tax) {
                        return [
                            'tax_id' => (int) $tax['tax_id'],
                            'tax_rate_id' => (int) $tax['tax_rate_id'],
                        ];
                    }, $line['taxes']);
                }

                return $mapped;
            }, $request['lines']);
        }

        $transaction = $service->createTransaction($this->user, $data);

        $icon = $transaction->type === 'income' ? '💰' : '💸';
        $category = $transaction->category ? " en categoría '{$transaction->category->name}'" : '';

        $response = "{$icon} Transacción registrada: {$transaction->description}{$category} - €" .
            number_format($transaction->amount, 2) . " el {$transaction->date->format('Y-m-d')}";

        if ($transaction->subtotal !== null) {
            $response .= "\n📊 Desglose: Subtotal €" . number_format($transaction->subtotal, 2);

            if ($transaction->tax_total > 0) {
                $response .= " + Impuestos €" . number_format($transaction->tax_total, 2);
            }

            if ($transaction->retention_total > 0) {
                $response .= " - Retenciones €" . number_format($transaction->retention_total, 2);
            }

            $response .= " = Total €" . number_format($transaction->amount, 2);
        }

        return $response;
    }
}
