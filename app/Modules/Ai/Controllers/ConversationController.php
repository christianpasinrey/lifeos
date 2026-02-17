<?php

namespace App\Modules\Ai\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = DB::table('agent_conversations')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->select('id', 'title', 'created_at', 'updated_at')
            ->get();

        return response()->json($conversations);
    }

    public function show(Request $request, string $id)
    {
        $conversation = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $conversation) {
            abort(404, 'Conversación no encontrada.');
        }

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $id)
            ->orderBy('created_at')
            ->select('id', 'role', 'content', 'created_at')
            ->get();

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate(['title' => 'required|string|max:100']);

        $updated = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['title' => $request->input('title')]);

        if (! $updated) {
            abort(404, 'Conversación no encontrada.');
        }

        return response()->json(['message' => 'Título actualizado.']);
    }

    public function destroy(Request $request, string $id)
    {
        $conversation = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $conversation) {
            abort(404, 'Conversación no encontrada.');
        }

        DB::table('agent_conversation_messages')
            ->where('conversation_id', $id)
            ->delete();

        DB::table('agent_conversations')
            ->where('id', $id)
            ->delete();

        return response()->json(['message' => 'Conversación eliminada.']);
    }
}
