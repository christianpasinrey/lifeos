<?php

namespace App\Modules\Finance;

use App\Events\FileAnalysisContextRequested;
use App\Modules\Ai\AiCoachRegistry;
use App\Modules\Finance\Commands\GenerateRecurringTransactions;
use App\Modules\Finance\Listeners\ProvideFinanceContext;
use Illuminate\Support\Facades\Event;
use App\Modules\Finance\Services\AccountService;
use App\Modules\Finance\Services\BudgetService;
use App\Modules\Finance\Services\CategoryService;
use App\Modules\Finance\Services\FinanceSummaryService;
use App\Modules\Finance\Services\ImportService;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Finance\Services\RecurringService;
use App\Modules\Finance\Services\TaxService;
use App\Modules\Finance\Services\TransactionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccountService::class);
        $this->app->singleton(TaxService::class);
        $this->app->singleton(CategoryService::class);
        $this->app->singleton(TransactionService::class);
        $this->app->singleton(FinanceSummaryService::class);
        $this->app->singleton(BudgetService::class);
        $this->app->singleton(RecurringService::class);
        $this->app->singleton(ImportService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(FinanceService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        Event::listen(FileAnalysisContextRequested::class, ProvideFinanceContext::class);

        $this->commands([
            GenerateRecurringTransactions::class,
        ]);

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new FinanceAiSpecialization());

            if ($this->app->bound(\App\Modules\Calendar\CalendarEventRegistry::class)) {
                app(\App\Modules\Calendar\CalendarEventRegistry::class)
                    ->register(new \App\Modules\Finance\Providers\FinanceCalendarProvider());
            }

            $schedule = app(Schedule::class);
            $schedule->command('finance:generate-recurring')->daily();
        });
    }
}
