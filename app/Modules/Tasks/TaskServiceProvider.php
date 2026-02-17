<?php

namespace App\Modules\Tasks;

use App\Modules\Ai\AiCoachRegistry;
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

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new TasksAiSpecialization());
        });
    }
}
