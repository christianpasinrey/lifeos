<?php

namespace App\Modules\Ai\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Agents\HabitCoach;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $agent = new HabitCoach($request->user());
        $response = $agent->prompt($request->input('message'));

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

        $agent = new HabitCoach($request->user());

        return $agent->stream($request->input('message'));
    }
}
