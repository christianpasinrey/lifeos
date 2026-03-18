<?php

namespace App\Modules\Notes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\Noteable;
use App\Modules\Notes\NoteService;
use App\Modules\Notes\Services\LinkExtractorService;
use App\Modules\Notes\Services\TagParserService;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(
        private NoteService $service,
    ) {}

    public function index(Request $request)
    {
        $query = $request->user()->notes()->with('folder', 'tags');

        if ($request->has('folder_id')) {
            $query->where('folder_id', $request->input('folder_id'));
        }

        if ($request->boolean('bookmarked')) {
            $query->where('is_bookmarked', true);
        }

        return response()->json(
            $query->orderBy('sort_order')->paginate(20)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'folder_id' => 'nullable|integer|exists:note_folders,id',
            'content' => 'nullable|string',
            'properties' => 'nullable|array',
        ]);

        if (isset($data['folder_id'])) {
            $folder = $request->user()->noteFolders()->findOrFail($data['folder_id']);
        }

        $note = $this->service->createNote($request->user(), $data);

        $this->extractLinksAndTags($note);

        return response()->json(['data' => $note->load('folder', 'tags')], 201);
    }

    public function show(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $note->load('folder', 'tags', 'outgoingLinks.target', 'incomingLinks.source', 'noteables');

        return response()->json(['data' => $note]);
    }

    public function update(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'folder_id' => 'nullable|integer|exists:note_folders,id',
            'content' => 'nullable|string',
            'properties' => 'nullable|array',
            'is_bookmarked' => 'sometimes|boolean',
        ]);

        $note = $this->service->updateNote($note, $data);

        if ($request->has('content') || $request->has('title')) {
            $this->extractLinksAndTags($note);
        }

        return response()->json(['data' => $note->load('folder', 'tags')]);
    }

    public function destroy(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $note->delete(); // soft delete

        return response()->json(['message' => 'Nota movida a la papelera.']);
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:1']);

        $q = $request->input('q');
        $user = $request->user();
        $query = $user->notes()->with('folder', 'tags');

        // Parse operators
        if (preg_match('/folder:(\S+)/', $q, $m)) {
            $query->whereHas('folder', fn ($f) => $f->where('name', 'like', "%{$m[1]}%"));
            $q = trim(str_replace($m[0], '', $q));
        }

        if (preg_match('/tag:(\S+)/', $q, $m)) {
            $query->whereHas('tags', fn ($t) => $t->where('full_path', 'like', "%{$m[1]}%"));
            $q = trim(str_replace($m[0], '', $q));
        }

        if (!empty($q)) {
            $query->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$q . '*']);
        }

        return response()->json(
            $query->orderBy('updated_at', 'desc')->paginate(20)
        );
    }

    public function trash(Request $request)
    {
        $notes = $request->user()->notes()
            ->onlyTrashed()
            ->select('id', 'title', 'slug', 'folder_id', 'deleted_at')
            ->orderBy('deleted_at', 'desc')
            ->paginate(20);

        return response()->json($notes);
    }

    public function restore(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $note->restore();

        return response()->json(['data' => $note->load('folder', 'tags')]);
    }

    public function linkEntity(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'noteable_type' => 'required|string|max:100',
            'noteable_id' => 'required|integer',
        ]);

        $noteable = Noteable::firstOrCreate([
            'note_id' => $note->id,
            'noteable_type' => $data['noteable_type'],
            'noteable_id' => $data['noteable_id'],
        ]);

        return response()->json(['data' => $noteable], 201);
    }

    public function unlinkEntity(Request $request, Note $note, Noteable $noteable)
    {
        abort_unless($note->user_id === $request->user()->id, 403);
        abort_unless($noteable->note_id === $note->id, 404);

        $noteable->delete();

        return response()->json(['message' => 'Vínculo eliminado.']);
    }

    private function extractLinksAndTags(Note $note): void
    {
        app(LinkExtractorService::class)->extract($note);
        app(TagParserService::class)->parse($note);
    }
}
