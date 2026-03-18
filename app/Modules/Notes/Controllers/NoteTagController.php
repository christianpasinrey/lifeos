<?php

namespace App\Modules\Notes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Models\NoteTag;
use Illuminate\Http\Request;

class NoteTagController extends Controller
{
    public function index(Request $request)
    {
        $tags = $request->user()->noteTags()
            ->withCount('notes')
            ->whereNull('parent_id')
            ->with('children', 'children.children')
            ->orderBy('full_path')
            ->get();

        return response()->json(['data' => $tags]);
    }

    public function destroy(Request $request, NoteTag $tag)
    {
        abort_unless($tag->user_id === $request->user()->id, 403);

        $tag->delete(); // cascade deletes children via FK

        return response()->json(['message' => 'Tag eliminado.']);
    }
}
