<?php

namespace App\Modules\Ai\Tools;

use App\Models\User;
use App\Modules\Habits\HabitService;
use App\Modules\Habits\Models\Habit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ToggleHabit implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Marca o desmarca un hábito como completado hoy. Para hábitos numéricos, registra un valor.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'habit_name' => $schema->string()
                ->description('Nombre del hábito a marcar/desmarcar')
                ->required(),
            'value' => $schema->number()
                ->description('Valor numérico (solo para hábitos numéricos)'),
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
        $result = $service->toggle($habit, $request->get('value'));

        $status = $result['completed'] ? 'completado' : 'desmarcado';
        $streak = $result['streak'] > 0 ? " (racha: {$result['streak']} días)" : '';

        return "Hábito '{$habit->name}' {$status}{$streak}.";
    }
}
