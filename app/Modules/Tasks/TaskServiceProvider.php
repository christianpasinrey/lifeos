<?php

namespace App\Modules\Tasks;

use App\Events\ActionRequested;
use App\Modules\Ai\AiCoachRegistry;
use App\Modules\Tasks\Listeners\CreateTaskFromAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaskService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        Event::listen(ActionRequested::class, CreateTaskFromAction::class);

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new TasksAiSpecialization());

            if ($this->app->bound(\App\Modules\Calendar\CalendarEventRegistry::class)) {
                app(\App\Modules\Calendar\CalendarEventRegistry::class)
                    ->register(new \App\Modules\Tasks\Providers\TasksCalendarProvider());
            }
        });
    }
}
