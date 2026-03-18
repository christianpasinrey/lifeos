<?php

namespace App\Modules\Notes;

use App\Models\User;
use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\NoteFolder;
use Illuminate\Support\Str;

class NoteService
{
    protected array $noteablePrefixes = [
        'task' => \App\Modules\Tasks\Models\Task::class,
        'event' => \App\Modules\Calendar\Models\CalendarEvent::class,
        'tx' => \App\Modules\Finance\Models\Transaction::class,
    ];

    public function createFolder(User $user, array $data): NoteFolder
    {
        $maxFolders = $user->getModuleLimit('notes', 'max_folders');

        if ($maxFolders !== null && $user->noteFolders()->count() >= $maxFolders) {
            abort(403, "Has alcanzado el límite de {$maxFolders} carpetas.");
        }

        $data['sort_order'] = $user->noteFolders()
            ->where('parent_id', $data['parent_id'] ?? null)
            ->count();

        return $user->noteFolders()->create($data);
    }

    public function createNote(User $user, array $data): Note
    {
        $maxNotes = $user->getModuleLimit('notes', 'max_notes');

        if ($maxNotes !== null && $user->notes()->count() >= $maxNotes) {
            abort(403, "Has alcanzado el límite de {$maxNotes} notas.");
        }

        $data['slug'] = $this->uniqueSlug($user, $data['title']);
        $data['sort_order'] = $user->notes()
            ->where('folder_id', $data['folder_id'] ?? null)
            ->count();

        return $user->notes()->create($data);
    }

    public function updateNote(Note $note, array $data): Note
    {
        if (isset($data['title']) && $data['title'] !== $note->title) {
            $data['slug'] = $this->uniqueSlug($note->user, $data['title'], $note->id);
        }

        $note->update($data);

        return $note;
    }

    public function getNoteablePrefixes(): array
    {
        return $this->noteablePrefixes;
    }

    private function uniqueSlug(User $user, string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title) ?: 'untitled';
        $slug = $base;
        $i = 1;

        while ($user->notes()->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
