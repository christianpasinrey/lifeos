<?php

namespace App\Modules\Ai\Tools;

use App\Models\User;
use App\Modules\Habits\HabitService;
use App\Modules\Habits\Models\Habit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetHabitStats implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene estadísticas detalladas de un hábito específico: rachas, porcentaje de cumplimiento, tendencias.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'habit_name' => $schema->string()
                ->description('El nombre del hábito del cual obtener estadísticas')
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
            "Estadísticas de '{$habit->name}':",
            "  Racha actual: {$stats['current_streak']} días",
            "  Mejor racha: {$stats['best_streak']} días",
            "  Total completados: {$stats['total_completions']}",
            "  Última semana: {$stats['rate_7']}%",
            "  Último mes: {$stats['rate_30']}%",
        ];

        if (isset($stats['average'])) {
            $lines[] = "  Promedio: {$stats['average']} {$habit->unit}";
            $lines[] = "  Mínimo: {$stats['min']} {$habit->unit}";
            $lines[] = "  Máximo: {$stats['max']} {$habit->unit}";
        }

        return implode("\n", $lines);
    }
}
