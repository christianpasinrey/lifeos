<?php

namespace App\Modules\Calendar\Tools;

use App\Models\User;
use App\Modules\Calendar\CalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateCalendarEventTool implements Tool
{
    public function __construct(private User $user) {}

    public function description(): Stringable|string
    {
        return 'Crea un nuevo evento en el calendario del usuario.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Título del evento')
                ->required(),
            'start_at' => $schema->string()
                ->description('Fecha/hora de inicio en formato YYYY-MM-DD o YYYY-MM-DD HH:mm')
                ->required(),
            'end_at' => $schema->string()
                ->description('Fecha/hora de fin (opcional)'),
            'all_day' => $schema->boolean()
                ->description('Si es un evento de todo el día (default: false)'),
            'type' => $schema->string()
                ->description('Tipo de evento: event, meeting, time_block (default: event)'),
            'location' => $schema->string()
                ->description('Ubicación del evento (opcional)'),
            'color' => $schema->string()
                ->description('Color hex del evento (default: #6366f1)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $data = [
            'title' => $request['title'],
            'start_at' => $request['start_at'],
            'end_at' => $request['end_at'] ?? null,
            'all_day' => $request['all_day'] ?? false,
            'type' => $request['type'] ?? 'event',
            'location' => $request['location'] ?? null,
            'color' => $request['color'] ?? '#6366f1',
        ];

        $service = app(CalendarService::class);
        $event = $service->createEvent($this->user, $data);

        return "✅ Evento '{$event->title}' creado exitosamente el {$event->start_at->format('Y-m-d H:i')} (ID: {$event->id}).";
    }
}
