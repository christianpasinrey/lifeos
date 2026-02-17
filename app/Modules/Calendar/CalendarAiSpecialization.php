<?php

namespace App\Modules\Calendar;

use App\Models\User;
use App\Modules\Ai\Contracts\AiSpecialization;
use App\Modules\Calendar\Tools\CreateCalendarEventTool;
use App\Modules\Calendar\Tools\GetCalendarEventsTool;
use App\Modules\Calendar\Tools\GetUpcomingTool;

class CalendarAiSpecialization implements AiSpecialization
{
    public function moduleSlug(): string
    {
        return 'calendar';
    }

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        ## Capacidades de Calendario

        Puedes ayudar al usuario a gestionar su agenda:
        - Consultar eventos del calendario en un rango de fechas (eventos propios, tareas, hábitos, finanzas)
        - Crear nuevos eventos en el calendario (citas, reuniones, bloques de tiempo)
        - Ver un resumen de los próximos días para planificar

        Los eventos pueden ser de diferentes fuentes:
        - **calendar**: Eventos propios del calendario
        - **tasks**: Tareas con fecha de vencimiento
        - **habits**: Hábitos programados para cada día
        - **finance**: Transacciones y pagos recurrentes

        Usa estas herramientas para ayudar al usuario a organizar su tiempo y estar al tanto de sus compromisos.
        INSTRUCTIONS;
    }

    public function tools(User $user): array
    {
        return [
            new GetCalendarEventsTool($user),
            new CreateCalendarEventTool($user),
            new GetUpcomingTool($user),
        ];
    }
}
