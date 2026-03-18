<?php

namespace App\Modules\Notes\Listeners;

use App\Events\ActionRequested;
use App\Modules\Notes\NoteService;
use Illuminate\Support\Facades\Gate;

class CreateNoteFromAction
{
    public function __construct(private NoteService $service) {}

    public function handle(ActionRequested $event): void
    {
        if ($event->type !== 'note') {
            return;
        }

        if (!Gate::forUser($event->user)->allows('module:notes')) {
            return;
        }

        $note = $this->service->createNote($event->user, [
            'title' => $event->title,
            'content' => $event->description ?? '',
        ]);

        $event->addResult('notes', $note);
    }
}
