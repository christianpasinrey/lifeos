<?php

namespace App\Modules\Tasks\Listeners;

use App\Events\ActionRequested;
use App\Modules\Tasks\TaskService;
use Illuminate\Support\Facades\Gate;

class CreateTaskFromAction
{
    public function __construct(private TaskService $service) {}

    public function handle(ActionRequested $event): void
    {
        if ($event->type !== 'task') {
            return;
        }

        if (!Gate::forUser($event->user)->allows('module:tasks')) {
            return;
        }

        $board = $event->user->boards()->where('name', 'Finanzas')->first()
            ?? $event->user->boards()->first();

        if (!$board) {
            $board = $this->service->createBoard($event->user, [
                'name' => 'Finanzas',
                'description' => 'Tareas financieras sugeridas por el análisis IA',
            ]);
        }

        $column = $board->columns()->orderBy('sort_order')->first();

        $task = $this->service->createTask($column, $event->user, [
            'title' => $event->title,
            'description' => $event->description,
        ]);

        $event->addResult('tasks', $task);
    }
}
