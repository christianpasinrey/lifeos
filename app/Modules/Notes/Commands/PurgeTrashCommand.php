<?php

namespace App\Modules\Notes\Commands;

use App\Modules\Notes\Models\Note;
use Illuminate\Console\Command;

class PurgeTrashCommand extends Command
{
    protected $signature = 'notes:purge-trash {--days=30 : Days after which trashed notes are permanently deleted}';
    protected $description = 'Permanently delete notes that have been in trash for more than N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $count = Note::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->forceDelete();

        $this->info("Purged {$count} notes from trash (older than {$days} days).");

        return self::SUCCESS;
    }
}
