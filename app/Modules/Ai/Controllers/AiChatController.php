<?php

namespace App\Modules\Ai\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Agents\HabitCoach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AiChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|string|max:36',
        ]);

        $this->enforceMessageLimit($request);

        $agent = $this->resolveAgent($request);
        $response = $agent->prompt($request->input('message'));

        $this->incrementMessageCount($request);

        return response()->json([
            'text' => $response->text,
            'conversation_id' => $response->conversationId,
            'usage' => [
                'input_tokens' => $response->usage->inputTokens ?? null,
                'output_tokens' => $response->usage->outputTokens ?? null,
            ],
        ]);
    }

    public function stream(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|string|max:36',
        ]);

        $this->enforceMessageLimit($request);
        $this->incrementMessageCount($request);

        $agent = $this->resolveAgent($request);

        return $agent->stream($request->input('message'));
    }

    private function resolveAgent(Request $request): HabitCoach
    {
        $agent = new HabitCoach($request->user());
        $conversationId = $request->input('conversation_id');

        if ($conversationId) {
            $agent->continue($conversationId, $request->user());
        } else {
            $this->enforceConversationLimit($request);
            $agent->forUser($request->user());
        }

        return $agent;
    }

    private function enforceMessageLimit(Request $request): void
    {
        $user = $request->user();
        $maxMessages = $user->getModuleLimit('ai_coach', 'max_messages_per_day');

        if ($maxMessages === null) {
            return;
        }

        $cacheKey = "ai_messages:{$user->id}:" . now()->toDateString();
        $count = Cache::get($cacheKey, 0);

        if ($count >= $maxMessages) {
            abort(429, "Has alcanzado el límite de {$maxMessages} mensajes diarios en tu plan actual.");
        }
    }

    private function enforceConversationLimit(Request $request): void
    {
        $user = $request->user();
        $maxConversations = $user->getModuleLimit('ai_coach', 'max_conversations');

        if ($maxConversations === null) {
            return;
        }

        $count = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->count();

        if ($count >= $maxConversations) {
            abort(429, "Has alcanzado el límite de {$maxConversations} conversaciones en tu plan actual. Elimina alguna para crear nuevas.");
        }
    }

    private function incrementMessageCount(Request $request): void
    {
        $user = $request->user();
        $cacheKey = "ai_messages:{$user->id}:" . now()->toDateString();

        Cache::put($cacheKey, Cache::get($cacheKey, 0) + 1, now()->endOfDay());
    }
}
