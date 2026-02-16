<?php

namespace App\Modules\Habits;

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
    }
}
