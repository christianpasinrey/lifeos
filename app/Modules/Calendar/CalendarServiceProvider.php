<?php

namespace App\Modules\Calendar;

use App\Modules\Ai\AiCoachRegistry;
use App\Modules\Calendar\Providers\NativeCalendarEventProvider;
use Illuminate\Support\ServiceProvider;

class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CalendarService::class);
        $this->app->singleton(CalendarEventRegistry::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->app->booted(function () {
            $registry = app(CalendarEventRegistry::class);
            $registry->register(app(NativeCalendarEventProvider::class));

            app(AiCoachRegistry::class)->register(new CalendarAiSpecialization());
        });
    }
}
