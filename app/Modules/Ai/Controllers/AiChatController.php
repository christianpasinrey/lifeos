<?php

namespace App\Modules\Ai\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Agents\HabitCoach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AiChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $this->enforceMessageLimit($request);

        $agent = new HabitCoach($request->user());
        $response = $agent->prompt($request->input('message'));

        $this->incrementMessageCount($request);

        return response()->json([
            'text' => $response->text,
            'usage' => [
                'input_tokens' => $response->usage->inputTokens ?? null,
                'output_tokens' => $response->usage->outputTokens ?? null,
            ],
        ]);
    }

    public function stream(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $this->enforceMessageLimit($request);
        $this->incrementMessageCount($request);

        $agent = new HabitCoach($request->user());

        return $agent->stream($request->input('message'));
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

    private function incrementMessageCount(Request $request): void
    {
        $user = $request->user();
        $cacheKey = "ai_messages:{$user->id}:" . now()->toDateString();

        Cache::put($cacheKey, Cache::get($cacheKey, 0) + 1, now()->endOfDay());
    }
}
