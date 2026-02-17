<?php

namespace App\Modules\CustomEntities;

use App\Modules\Ai\AiCoachRegistry;
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

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new CustomEntitiesAiSpecialization());
        });
    }
}
