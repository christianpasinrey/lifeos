<?php

namespace App\Modules\Notes;

use App\Events\ActionRequested;
use App\Modules\Ai\AiCoachRegistry;
use App\Modules\Notes\Listeners\CreateNoteFromAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class NoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NoteService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        Event::listen(ActionRequested::class, CreateNoteFromAction::class);

        $this->app->booted(function () {
            app(AiCoachRegistry::class)->register(new NotesAiSpecialization());
        });
    }
}
