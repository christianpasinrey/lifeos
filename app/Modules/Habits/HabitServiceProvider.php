<?php

namespace App\Modules\Habits;

use App\Modules\Ai\AiCoachRegistry;
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

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new HabitsAiSpecialization());
        });
    }
}
