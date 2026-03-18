<?php

namespace App\Modules\Notes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Models\Note;
use Illuminate\Http\Request;

class NoteLinkController extends Controller
{
    public function backlinks(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $backlinks = $note->incomingLinks()
            ->with('source:id,title,slug')
            ->select('id', 'source_note_id', 'link_type', 'context')
            ->paginate(20);

        return response()->json($backlinks);
    }

    public function unlinked(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $searchTerms = collect([$note->title])
            ->merge($note->aliases)
            ->filter()
            ->unique();

        if ($searchTerms->isEmpty()) {
            return response()->json(['data' => []]);
        }

        // Find notes mentioning title/aliases but not already linked
        $linkedIds = $note->incomingLinks()->pluck('source_note_id');

        $query = Note::where('user_id', $request->user()->id)
            ->where('id', '!=', $note->id)
            ->whereNotIn('id', $linkedIds);

        $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('content', 'like', "%{$term}%");
            }
        });

        $mentions = $query->select('id', 'title', 'slug', 'content')
            ->limit(20)
            ->get()
            ->map(function ($n) use ($searchTerms) {
                // Extract context around first mention
                $context = '';
                foreach ($searchTerms as $term) {
                    $pos = mb_stripos($n->content ?? '', $term);
                    if ($pos !== false) {
                        $start = max(0, $pos - 30);
                        $context = mb_substr($n->content, $start, 80);
                        break;
                    }
                }
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'slug' => $n->slug,
                    'context' => $context,
                ];
            });

        return response()->json(['data' => $mentions]);
    }
}
