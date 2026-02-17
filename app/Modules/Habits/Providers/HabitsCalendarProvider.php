<?php

namespace App\Modules\Habits\Providers;

use App\Models\User;
use App\Modules\Calendar\Contracts\CalendarEventProvider;
use App\Modules\Calendar\DTOs\CalendarEventDTO;
use App\Modules\Habits\Models\Habit;
use App\Modules\Habits\Models\HabitLog;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HabitsCalendarProvider implements CalendarEventProvider
{
    public function source(): string
    {
        return 'habits';
    }

    public function label(): string
    {
        return 'Hábitos';
    }

    public function color(): string
    {
        return '#10b981';
    }

    public function events(User $user, Carbon $start, Carbon $end): array
    {
        $habits = Habit::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($habits->isEmpty()) {
            return [];
        }

        // Prefetch logs for the date range
        $habitIds = $habits->pluck('id');
        $logs = HabitLog::whereIn('habit_id', $habitIds)
            ->whereBetween('completed_at', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($log) => $log->habit_id . '_' . $log->completed_at);

        $events = [];
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            foreach ($habits as $habit) {
                if (!$habit->isDueOnDate($date)) {
                    continue;
                }

                if ($habit->isOnVacationOn($date)) {
                    continue;
                }

                $logKey = $habit->id . '_' . $date->toDateString();
                $hasLog = $logs->has($logKey);

                $events[] = new CalendarEventDTO(
                    id: "habit_{$habit->id}_{$date->format('Y-m-d')}",
                    title: ($habit->icon ? $habit->icon . ' ' : '') . $habit->name,
                    description: $habit->description,
                    start: $date->format('Y-m-d'),
                    end: null,
                    allDay: true,
                    source: 'habits',
                    color: $hasLog ? '#10b981' : '#6b7280',
                    icon: $habit->icon,
                    meta: [
                        'habit_id' => $habit->id,
                        'completed' => $hasLog,
                        'frequency' => $habit->frequency,
                    ],
                );
            }
        }

        return $events;
    }
}
