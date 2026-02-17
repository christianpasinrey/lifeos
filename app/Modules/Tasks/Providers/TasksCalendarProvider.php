<?php

namespace App\Modules\Tasks\Providers;

use App\Models\User;
use App\Modules\Calendar\Contracts\CalendarEventProvider;
use App\Modules\Calendar\DTOs\CalendarEventDTO;
use App\Modules\Tasks\Models\Task;
use Carbon\Carbon;

class TasksCalendarProvider implements CalendarEventProvider
{
    public function source(): string
    {
        return 'tasks';
    }

    public function label(): string
    {
        return 'Tareas';
    }

    public function color(): string
    {
        return '#60a5fa';
    }

    public function events(User $user, Carbon $start, Carbon $end): array
    {
        $tasks = Task::where('user_id', $user->id)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return $tasks->map(function (Task $task) {
            $color = match ($task->priority) {
                'high' => '#f43f5e',
                'medium' => '#f59e0b',
                default => '#60a5fa',
            };

            return new CalendarEventDTO(
                id: "task_{$task->id}",
                title: $task->title,
                description: $task->description,
                start: $task->due_date->format('Y-m-d'),
                end: null,
                allDay: true,
                source: 'tasks',
                color: $color,
                icon: null,
                meta: [
                    'task_id' => $task->id,
                    'priority' => $task->priority,
                    'column_id' => $task->column_id,
                ],
            );
        })->all();
    }
}
