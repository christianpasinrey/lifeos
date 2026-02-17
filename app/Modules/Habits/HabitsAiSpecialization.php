<?php

namespace App\Modules\Habits;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;
use App\Modules\Ai\Tools\CreateHabit;
use App\Modules\Ai\Tools\GetHabitStats;
use App\Modules\Ai\Tools\GetUserHabits;
use App\Modules\Ai\Tools\ToggleHabit;

class HabitsAiSpecialization implements AiSpecialization
{
    public function moduleSlug(): string
    {
        return 'habits';
    }

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        ## Capacidades de Hábitos
        
        Puedes ayudar al usuario con sus hábitos diarios:
        - Ver los hábitos del usuario y su progreso de hoy
        - Ver estadísticas detalladas de cualquier hábito (rachas, porcentajes, tendencias)
        - Crear nuevos hábitos cuando el usuario lo pida
        - Marcar o desmarcar hábitos como completados
        
        Usa estas herramientas para consultar y modificar los hábitos del usuario.
        INSTRUCTIONS;
    }

    public function tools(User $user): array
    {
        return [
            new GetUserHabits($user),
            new GetHabitStats($user),
            new CreateHabit($user),
            new ToggleHabit($user),
        ];
    }
}
