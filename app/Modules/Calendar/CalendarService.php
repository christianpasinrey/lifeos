<?php

namespace App\Modules\Calendar;

use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;
use Carbon\Carbon;

class CalendarService
{
    public function getEvents(User $user, Carbon $start, Carbon $end)
    {
        return CalendarEvent::where('user_id', $user->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_at', '<=', $end)
                          ->where('end_at', '>=', $start);
                    });
            })
            ->orderBy('start_at')
            ->get();
    }

    public function createEvent(User $user, array $data): CalendarEvent
    {
        return CalendarEvent::create([
            'user_id' => $user->id,
            ...$data,
        ]);
    }

    public function updateEvent(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->update($data);
        return $event->fresh();
    }

    public function deleteEvent(CalendarEvent $event): void
    {
        $event->delete();
    }
}
