<?php

namespace App\Modules\Notes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\NoteFolder;
use App\Modules\Notes\NoteService;
use Illuminate\Http\Request;

class DailyNoteController extends Controller
{
    public function __construct(private NoteService $service) {}

    public function store(Request $request)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');

        // Check if today's note exists
        $existing = $user->notes()->where('title', $today)->first();

        if ($existing) {
            return response()->json(['data' => $existing]);
        }

        // Find or create Daily folder
        $folder = $user->noteFolders()
            ->where('name', 'Daily')
            ->whereNull('parent_id')
            ->first();

        if (!$folder) {
            $folder = $this->service->createFolder($user, ['name' => 'Daily']);
        }

        // Find daily template
        $template = $user->notes()
            ->whereJsonContains('properties->template', true)
            ->whereHas('folder', fn ($q) => $q->where('name', 'Templates'))
            ->where('title', 'like', '%daily%')
            ->first();

        $content = '';
        if ($template) {
            $content = str_replace(
                ['{{title}}', '{{date}}', '{{time}}'],
                [$today, $today, now()->format('H:i')],
                $template->content ?? ''
            );
        }

        $note = $this->service->createNote($user, [
            'title' => $today,
            'folder_id' => $folder->id,
            'content' => $content,
        ]);

        return response()->json(['data' => $note], 201);
    }
}
