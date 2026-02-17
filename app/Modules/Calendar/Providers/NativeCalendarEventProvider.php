<?php

namespace App\Modules\Calendar\Providers;

use App\Models\User;
use App\Modules\Calendar\CalendarService;
use App\Modules\Calendar\Contracts\CalendarEventProvider;
use App\Modules\Calendar\DTOs\CalendarEventDTO;
use Carbon\Carbon;

class NativeCalendarEventProvider implements CalendarEventProvider
{
    public function __construct(private CalendarService $service) {}

    public function source(): string
    {
        return 'calendar';
    }

    public function label(): string
    {
        return 'Calendario';
    }

    public function color(): string
    {
        return '#6366f1';
    }

    public function events(User $user, Carbon $start, Carbon $end): array
    {
        return $this->service->getEvents($user, $start, $end)
            ->map(function ($event) {
                return new CalendarEventDTO(
                    id: "calendar_{$event->id}",
                    title: $event->title,
                    description: $event->description,
                    start: $event->all_day
                        ? $event->start_at->format('Y-m-d')
                        : $event->start_at->format('Y-m-d H:i'),
                    end: $event->end_at
                        ? ($event->all_day
                            ? $event->end_at->format('Y-m-d')
                            : $event->end_at->format('Y-m-d H:i'))
                        : null,
                    allDay: $event->all_day,
                    source: 'calendar',
                    color: $event->color ?? '#6366f1',
                    icon: null,
                    meta: [
                        'native_id' => $event->id,
                        'type' => $event->type,
                        'location' => $event->location,
                    ],
                );
            })
            ->all();
    }
}
