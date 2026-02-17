<?php

namespace App\Modules\Finance\Commands;

use App\Modules\Finance\Services\RecurringService;
use Illuminate\Console\Command;

class GenerateRecurringTransactions extends Command
{
    protected $signature = 'finance:generate-recurring';
    protected $description = 'Generate due recurring transactions';

    public function handle(RecurringService $service): int
    {
        $count = $service->generateDueTransactions();

        $this->info("Generated {$count} recurring transaction(s).");

        return self::SUCCESS;
    }
}
