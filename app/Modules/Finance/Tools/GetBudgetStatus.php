<?php

namespace App\Modules\Finance\Tools;

use App\Models\User;
use App\Modules\Finance\Services\BudgetService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetBudgetStatus implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene el estado de los presupuestos del usuario: límite, gasto actual, porcentaje y si está por encima del presupuesto.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'budget_id' => $schema->integer()
                ->description('ID de un presupuesto específico (opcional, si no se indica muestra todos los activos)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(BudgetService::class);

        if (!empty($request['budget_id'])) {
            $budget = $this->user->hasMany(\App\Modules\Finance\Models\Budget::class)
                ->find($request['budget_id']);

            if (!$budget) {
                return '❌ Presupuesto no encontrado.';
            }

            $progress = $service->getProgress($budget);
            return $this->formatBudgetProgress($progress);
        }

        $allProgress = $service->getAllProgress($this->user);

        if (empty($allProgress)) {
            return 'No hay presupuestos activos configurados.';
        }

        $output = "📊 **Estado de presupuestos** ({$this->countLabel(count($allProgress))}):\n\n";

        foreach ($allProgress as $progress) {
            $output .= $this->formatBudgetProgress($progress) . "\n";
        }

        $overBudget = array_filter($allProgress, fn ($p) => $p['over_budget']);
        if (count($overBudget) > 0) {
            $output .= "⚠️ **{$this->countLabel(count($overBudget))} por encima del límite.**\n";
        }

        return $output;
    }

    private function formatBudgetProgress(array $progress): string
    {
        $icon = $progress['over_budget'] ? '🔴' : ($progress['percentage'] >= 80 ? '🟡' : '🟢');
        $output = "{$icon} **{$progress['name']}**: €" . number_format($progress['spent'], 2) .
                  " / €" . number_format($progress['limit'], 2) .
                  " ({$progress['percentage']}%)\n";
        $output .= "   Restante: €" . number_format($progress['remaining'], 2) .
                   " | Período: {$progress['period_start']} a {$progress['period_end']}\n";

        return $output;
    }

    private function countLabel(int $count): string
    {
        return $count === 1 ? '1 presupuesto' : "{$count} presupuestos";
    }
}
