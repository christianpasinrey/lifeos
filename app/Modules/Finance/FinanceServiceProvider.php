<?php

namespace App\Modules\Finance;

use App\Modules\Ai\AiCoachRegistry;
use App\Modules\Finance\Commands\GenerateRecurringTransactions;
use App\Modules\Finance\Services\AccountService;
use App\Modules\Finance\Services\BudgetService;
use App\Modules\Finance\Services\CategoryService;
use App\Modules\Finance\Services\FinanceSummaryService;
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
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(FinanceService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        $this->commands([
            GenerateRecurringTransactions::class,
        ]);

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new FinanceAiSpecialization());

            $schedule = app(Schedule::class);
            $schedule->command('finance:generate-recurring')->daily();
        });
    }
}
