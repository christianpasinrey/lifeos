<?php

namespace App\Modules\Ai\Tools;

use App\Models\User;
use App\Modules\Habits\HabitService;
use App\Modules\Habits\Models\Habit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SuggestHabitImprovements implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene datos detallados de un hábito específico para generar recomendaciones de mejora personalizadas.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'habit_name' => $schema->string()
                ->description('Nombre del hábito a analizar')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $habit = Habit::where('user_id', $this->user->id)
            ->where('name', 'like', '%' . $request->get('habit_name') . '%')
            ->first();

        if (!$habit) {
            return "No encontré un hábito con ese nombre.";
        }

        $service = app(HabitService::class);
        $stats = $service->getStats($habit);

        $lines = [
            "Datos detallados de '{$habit->name}' para recomendaciones:",
            "",
            "Configuración:",
            "  Tipo: {$habit->type}" . ($habit->type === 'numeric' ? " ({$habit->unit}, objetivo: {$habit->target_value})" : ''),
            "  Frecuencia: {$habit->frequency}",
            "  Rutina: " . ($habit->routine ?? 'anytime'),
            "  Días objetivo: " . ($habit->target_days ? implode(', ', $habit->target_days) : 'todos'),
            "",
            "Rendimiento:",
            "  Racha actual: {$stats['current_streak']}",
            "  Mejor racha: {$stats['best_streak']}",
            "  Tasa 7 días: {$stats['rate_7']}%",
            "  Tasa 30 días: {$stats['rate_30']}%",
            "  Total completados: {$stats['total_completions']}",
            "  Creado: {$habit->created_at->format('Y-m-d')}",
        ];

        if (isset($stats['day_of_week'])) {
            $lines[] = "";
            $lines[] = "Consistencia por día:";
            $dayLabels = ['mon' => 'Lun', 'tue' => 'Mar', 'wed' => 'Mié', 'thu' => 'Jue', 'fri' => 'Vie', 'sat' => 'Sáb', 'sun' => 'Dom'];
            foreach ($stats['day_of_week'] as $day => $rate) {
                $lines[] = "  {$dayLabels[$day]}: {$rate}%";
            }
        }

        if (isset($stats['average'])) {
            $lines[] = "";
            $lines[] = "Valores numéricos:";
            $lines[] = "  Promedio: {$stats['average']} {$habit->unit}";
            $lines[] = "  Min: {$stats['min']} | Max: {$stats['max']}";
        }

        if ($stats['recent_notes']->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "Notas recientes:";
            foreach ($stats['recent_notes']->take(5) as $note) {
                $lines[] = "  [{$note['date']}] {$note['notes']}";
            }
        }

        $lines[] = "";
        $lines[] = "Genera recomendaciones específicas y accionables basándote en estos datos.";

        return implode("\n", $lines);
    }
}
