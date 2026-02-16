<?php

namespace App\Modules\CustomEntities;

use Illuminate\Support\ServiceProvider;

class CustomEntityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CustomEntityService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
