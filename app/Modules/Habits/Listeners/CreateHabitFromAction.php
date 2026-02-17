<?php

namespace App\Modules\Habits\Listeners;

use App\Events\ActionRequested;
use App\Modules\Habits\HabitService;
use Illuminate\Support\Facades\Gate;

class CreateHabitFromAction
{
    public function __construct(private HabitService $service) {}

    public function handle(ActionRequested $event): void
    {
        if ($event->type !== 'habit') {
            return;
        }

        if (!Gate::forUser($event->user)->allows('module:habits')) {
            return;
        }

        $habit = $this->service->create($event->user, [
            'name' => $event->title,
            'type' => 'boolean',
            'frequency' => 'daily',
            'routine' => $event->meta['routine'] ?? 'morning',
            'color' => $event->meta['color'] ?? '#10b981',
        ]);

        $event->addResult('habits', $habit);
    }
}
