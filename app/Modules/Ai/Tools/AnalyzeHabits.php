<?php

namespace App\Modules\Ai\Tools;

use App\Models\User;
use App\Modules\Habits\HabitService;
use App\Modules\Habits\Models\HabitLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AnalyzeHabits implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Analiza patrones de todos los hábitos del usuario en los últimos 30 días. Identifica días débiles, hábitos en riesgo, y patrones generales.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $service = app(HabitService::class);
        $habits = $this->user->habits()->where('is_active', true)->get();

        if ($habits->isEmpty()) {
            return "El usuario no tiene hábitos activos.";
        }

        $thirtyDaysAgo = now()->subDays(30);

        $lines = ["Análisis de hábitos (últimos 30 días):"];
        $lines[] = "Total hábitos activos: {$habits->count()}";
        $lines[] = "";

        $dayCompletions = ['mon' => 0, 'tue' => 0, 'wed' => 0, 'thu' => 0, 'fri' => 0, 'sat' => 0, 'sun' => 0];
        $dayTotals = ['mon' => 0, 'tue' => 0, 'wed' => 0, 'thu' => 0, 'fri' => 0, 'sat' => 0, 'sun' => 0];

        foreach ($habits as $habit) {
            $stats = $service->getStats($habit);
            $status = $stats['rate_30'] >= 80 ? '✅ Excelente' : ($stats['rate_30'] >= 50 ? '⚠️ En riesgo' : '❌ Crítico');

            $lines[] = "{$habit->name}: {$stats['rate_30']}% ({$status})";
            $lines[] = "  Racha: {$stats['current_streak']} | Mejor: {$stats['best_streak']} | Semana: {$stats['rate_7']}%";

            if (isset($stats['day_of_week'])) {
                foreach ($stats['day_of_week'] as $day => $rate) {
                    $dayCompletions[$day] += $rate;
                    $dayTotals[$day]++;
                }
            }

            $lines[] = "";
        }

        // Day of week summary
        $lines[] = "Consistencia por día (promedio todos los hábitos):";
        $dayLabels = ['mon' => 'Lunes', 'tue' => 'Martes', 'wed' => 'Miércoles', 'thu' => 'Jueves', 'fri' => 'Viernes', 'sat' => 'Sábado', 'sun' => 'Domingo'];
        foreach ($dayLabels as $key => $label) {
            $avg = $dayTotals[$key] > 0 ? round($dayCompletions[$key] / $dayTotals[$key]) : 0;
            $lines[] = "  {$label}: {$avg}%";
        }

        return implode("\n", $lines);
    }
}
