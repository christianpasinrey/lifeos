<?php

namespace App\Modules\Ai\Agents;

use App\Models\User;
use App\Modules\Ai\Tools\CreateHabit;
use App\Modules\Ai\Tools\GetHabitStats;
use App\Modules\Ai\Tools\GetUserHabits;
use App\Modules\Ai\Tools\ToggleHabit;
use App\Modules\Ai\Tools\CreateCustomEntity;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class HabitCoach implements Agent, HasTools
{
    use Promptable;

    public function __construct(private User $user) {}

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
        Eres un coach personal de hábitos y bienestar. Tu nombre es Coach.
        Tienes acceso a los hábitos y estadísticas reales del usuario.

        Tus capacidades:
        - Ver los hábitos del usuario y su progreso de hoy
        - Ver estadísticas detalladas de cualquier hábito (rachas, %, tendencias)
        - Crear nuevos hábitos cuando el usuario lo pida
        - Marcar/desmarcar hábitos como completados
        - Crear entidades personalizadas para trackear cualquier cosa nueva

        Reglas:
        - Responde SIEMPRE en español
        - Sé conciso, empático y motivador
        - Usa los datos reales del usuario para personalizar tus respuestas
        - Cuando el usuario pida crear un hábito, usa la herramienta directamente
        - Si el usuario quiere trackear algo que no encaja en hábitos, usa CreateCustomEntity
        - No inventes datos, usa siempre las herramientas para consultar
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new GetUserHabits($this->user),
            new GetHabitStats($this->user),
            new CreateHabit($this->user),
            new ToggleHabit($this->user),
            new CreateCustomEntity($this->user),
        ];
    }
}
