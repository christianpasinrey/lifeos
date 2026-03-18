<?php

namespace App\Modules\Notes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Models\NoteFolder;
use App\Modules\Notes\NoteService;
use Illuminate\Http\Request;

class NoteFolderController extends Controller
{
    public function __construct(private NoteService $service) {}

    public function index(Request $request)
    {
        $folders = $request->user()->noteFolders()
            ->with('children', 'children.children', 'children.children.children')
            ->withCount('allNotes')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        // Also get root-level notes (no folder)
        $rootNotes = $request->user()->notes()
            ->whereNull('folder_id')
            ->select('id', 'title', 'slug', 'is_bookmarked', 'sort_order', 'updated_at')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => [
                'folders' => $folders,
                'root_notes' => $rootNotes,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:note_folders,id',
        ]);

        if (isset($data['parent_id'])) {
            $request->user()->noteFolders()->findOrFail($data['parent_id']);
        }

        $folder = $this->service->createFolder($request->user(), $data);

        return response()->json(['data' => $folder], 201);
    }

    public function update(Request $request, NoteFolder $noteFolder)
    {
        abort_unless($noteFolder->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|integer|exists:note_folders,id',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        $noteFolder->update($data);

        return response()->json(['data' => $noteFolder]);
    }

    public function destroy(Request $request, NoteFolder $noteFolder)
    {
        abort_unless($noteFolder->user_id === $request->user()->id, 403);

        $noteFolder->delete();

        return response()->json(['message' => 'Carpeta eliminada.']);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:note_folders,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|integer',
        ]);

        foreach ($data['items'] as $item) {
            $request->user()->noteFolders()
                ->where('id', $item['id'])
                ->update([
                    'sort_order' => $item['sort_order'],
                    'parent_id' => $item['parent_id'] ?? null,
                ]);
        }

        return response()->json(['message' => 'Carpetas reordenadas.']);
    }
}
