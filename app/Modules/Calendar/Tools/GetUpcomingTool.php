<?php

namespace App\Modules\Calendar\Tools;

use App\Models\User;
use App\Modules\Calendar\CalendarEventRegistry;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetUpcomingTool implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Obtiene un resumen de los próximos días del calendario, agrupado por día.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()
                ->description('Número de días a consultar (default: 7)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $days = (int) ($request['days'] ?? 7);
        $days = max(1, min($days, 30));

        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->addDays($days)->endOfDay();

        $registry = app(CalendarEventRegistry::class);
        $events = $registry->getEvents($this->user, $start, $end);

        if (empty($events)) {
            return "No tienes eventos en los próximos {$days} días.";
        }

        $grouped = [];
        foreach ($events as $event) {
            $date = substr($event->start, 0, 10);
            $grouped[$date][] = $event;
        }

        $dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $lines = ["📅 Agenda de los próximos {$days} días:"];

        foreach ($grouped as $date => $dayEvents) {
            $carbon = Carbon::parse($date);
            $dayName = $dayNames[$carbon->dayOfWeek];
            $isToday = $carbon->isToday();
            $label = $isToday ? "Hoy ({$dayName})" : ucfirst($dayName) . " {$date}";

            $lines[] = "\n🗓️ {$label}:";
            foreach ($dayEvents as $event) {
                $time = $event->allDay ? '◻️' : '🕐 ' . substr($event->start, 11, 5);
                $source = strtoupper($event->source);
                $lines[] = "  {$time} [{$source}] {$event->title}";
            }
        }

        $lines[] = "\nTotal: " . count($events) . " eventos en {$days} días.";

        return implode("\n", $lines);
    }
}
