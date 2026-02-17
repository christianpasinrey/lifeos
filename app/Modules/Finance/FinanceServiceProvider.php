<?php

namespace App\Modules\Finance;

use App\Modules\Ai\AiCoachRegistry;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinanceService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new FinanceAiSpecialization());
        });
    }
}
