<?php

namespace App\Modules\Notes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\NoteLink;
use Illuminate\Http\Request;

class NoteGraphController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'scope' => 'sometimes|in:global,local',
            'note_id' => 'sometimes|integer|exists:notes,id',
            'depth' => 'sometimes|integer|min:1|max:5',
        ]);

        $userId = $request->user()->id;
        $scope = $request->input('scope', 'global');

        if ($scope === 'local' && $request->has('note_id')) {
            return $this->localGraph($userId, $request->integer('note_id'), $request->integer('depth', 2));
        }

        return $this->globalGraph($userId);
    }

    private function globalGraph(int $userId)
    {
        $notes = Note::where('user_id', $userId)
            ->select('id', 'title', 'slug', 'folder_id')
            ->with('tags:id,full_path')
            ->get();

        $noteIds = $notes->pluck('id');

        $edges = NoteLink::whereIn('source_note_id', $noteIds)
            ->whereNotNull('target_note_id')
            ->select('source_note_id', 'target_note_id', 'link_type')
            ->get();

        return response()->json([
            'nodes' => $notes->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'slug' => $n->slug,
                'folder_id' => $n->folder_id,
                'tags' => $n->tags->pluck('full_path'),
            ]),
            'edges' => $edges->map(fn ($e) => [
                'source' => $e->source_note_id,
                'target' => $e->target_note_id,
                'type' => $e->link_type,
            ]),
        ]);
    }

    private function localGraph(int $userId, int $noteId, int $depth)
    {
        $visited = collect();
        $queue = collect([$noteId]);
        $allEdges = collect();

        for ($d = 0; $d < $depth && $queue->isNotEmpty(); $d++) {
            $visited = $visited->merge($queue);

            $outgoing = NoteLink::whereIn('source_note_id', $queue)
                ->whereNotNull('target_note_id')
                ->select('source_note_id', 'target_note_id', 'link_type')
                ->get();

            $incoming = NoteLink::whereIn('target_note_id', $queue)
                ->select('source_note_id', 'target_note_id', 'link_type')
                ->get();

            $allEdges = $allEdges->merge($outgoing)->merge($incoming);

            $newIds = $outgoing->pluck('target_note_id')
                ->merge($incoming->pluck('source_note_id'))
                ->unique()
                ->diff($visited);

            $queue = $newIds->values();
        }

        $visited = $visited->merge($queue)->unique();

        $notes = Note::where('user_id', $userId)
            ->whereIn('id', $visited)
            ->select('id', 'title', 'slug', 'folder_id')
            ->with('tags:id,full_path')
            ->get();

        return response()->json([
            'nodes' => $notes->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'slug' => $n->slug,
                'folder_id' => $n->folder_id,
                'tags' => $n->tags->pluck('full_path'),
            ]),
            'edges' => $allEdges->unique(fn ($e) => $e->source_note_id . '-' . $e->target_note_id)
                ->map(fn ($e) => [
                    'source' => $e->source_note_id,
                    'target' => $e->target_note_id,
                    'type' => $e->link_type,
                ])->values(),
        ]);
    }
}
