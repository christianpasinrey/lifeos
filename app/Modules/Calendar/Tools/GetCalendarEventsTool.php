<?php

namespace App\Modules\Calendar\Tools;

use App\Models\User;
use App\Modules\Calendar\CalendarEventRegistry;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCalendarEventsTool implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene los eventos del calendario en un rango de fechas, incluyendo eventos propios, tareas, hábitos y finanzas.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('Fecha de inicio en formato YYYY-MM-DD')
                ->required(),
            'end_date' => $schema->string()
                ->description('Fecha de fin en formato YYYY-MM-DD')
                ->required(),
            'sources' => $schema->string()
                ->description('Fuentes separadas por coma: calendar,tasks,habits,finance. Si se omite, se incluyen todas.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $start = Carbon::parse($request['start_date'])->startOfDay();
        $end = Carbon::parse($request['end_date'])->endOfDay();
        $sources = isset($request['sources']) ? explode(',', $request['sources']) : null;

        $registry = app(CalendarEventRegistry::class);
        $events = $registry->getEvents($this->user, $start, $end, $sources);

        if (empty($events)) {
            return "No hay eventos entre {$request['start_date']} y {$request['end_date']}.";
        }

        $lines = ["Eventos del {$request['start_date']} al {$request['end_date']}:"];
        $currentDate = '';

        foreach ($events as $event) {
            $eventDate = substr($event->start, 0, 10);
            if ($eventDate !== $currentDate) {
                $currentDate = $eventDate;
                $lines[] = "\n📅 {$currentDate}:";
            }

            $time = $event->allDay ? '(todo el día)' : substr($event->start, 11, 5);
            $source = strtoupper($event->source);
            $lines[] = "  [{$source}] {$time} - {$event->title}";
        }

        $lines[] = "\nTotal: " . count($events) . " eventos.";

        return implode("\n", $lines);
    }
}
