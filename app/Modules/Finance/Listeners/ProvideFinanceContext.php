<?php

namespace App\Modules\Finance\Listeners;

use App\Events\FileAnalysisContextRequested;
use Illuminate\Support\Facades\Gate;

class ProvideFinanceContext
{
    public function handle(FileAnalysisContextRequested $event): void
    {
        if (!Gate::forUser($event->user)->allows('module:finance')) {
            return;
        }

        $categories = $event->user->categories()->get(['id', 'name', 'type', 'color']);

        if ($categories->isEmpty()) {
            return;
        }

        $catList = $categories->map(fn($c) => "- [{$c->id}] {$c->name} ({$c->type})")->join("\n");

        $context = "Categorías financieras del usuario:\n{$catList}\n";
        $context .= "Cuando propongas transacciones, sugiere la categoría más adecuada de esta lista. Si ninguna encaja, propón crear una nueva.";

        $event->addContext($context);
    }
}
