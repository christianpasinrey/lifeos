<?php

namespace App\Modules\Finance\Commands;

use App\Modules\Finance\Services\Banking\BankSyncService;
use Illuminate\Console\Command;

class SyncBanks extends Command
{
    protected $signature = 'finance:sync-banks';
    protected $description = 'Sync all active bank connections';

    public function handle(BankSyncService $service): int
    {
        $count = $service->syncAllActive();

        $this->info("Synced {$count} transaction(s) across all active bank connections.");

        return self::SUCCESS;
    }
}
