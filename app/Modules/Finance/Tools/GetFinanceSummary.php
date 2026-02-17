<?php

namespace App\Modules\Finance\Tools;

use App\Models\User;
use App\Modules\Finance\FinanceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetFinanceSummary implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene el resumen financiero del usuario: ingresos totales, gastos totales, balance y desglose por categorías para un período específico.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('Fecha de inicio del período en formato YYYY-MM-DD (opcional, por defecto: inicio del mes actual)'),
            'end_date' => $schema->string()
                ->description('Fecha de fin del período en formato YYYY-MM-DD (opcional, por defecto: fin del mes actual)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(FinanceService::class);

        $summary = $service->getSummary(
            $this->user,
            $request->string('start_date'),
            $request->string('end_date')
        );

        $output = "📊 **Resumen Financiero** ({$summary['period']['start']} - {$summary['period']['end']})\n\n";
        $output .= "💰 **Ingresos totales:** €" . number_format($summary['income'], 2) . "\n";
        $output .= "💸 **Gastos totales:** €" . number_format($summary['expenses'], 2) . "\n";
        $output .= "📈 **Balance:** €" . number_format($summary['balance'], 2);

        if ($summary['balance'] >= 0) {
            $output .= " ✅ (superávit)\n";
        } else {
            $output .= " ⚠️ (déficit)\n";
        }

        if ($summary['by_category']->isNotEmpty()) {
            $output .= "\n**Desglose por categoría:**\n";
            foreach ($summary['by_category'] as $cat) {
                $output .= "  - {$cat['category']}: €" . number_format($cat['total'], 2) . " ({$cat['count']} transacciones)\n";
            }
        }

        return $output;
    }
}
