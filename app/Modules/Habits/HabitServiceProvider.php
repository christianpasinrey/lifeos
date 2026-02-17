<?php

namespace App\Modules\Habits;

use App\Events\ActionRequested;
use App\Modules\Ai\AiCoachRegistry;
use App\Modules\Habits\Listeners\CreateHabitFromAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class HabitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HabitService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        Event::listen(ActionRequested::class, CreateHabitFromAction::class);

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new HabitsAiSpecialization());
        });
    }
}
