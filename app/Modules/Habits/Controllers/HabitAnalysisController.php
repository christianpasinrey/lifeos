<?php

namespace App\Modules\Habits\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\Agents\HabitAnalyzer;
use App\Modules\Habits\HabitService;
use Illuminate\Http\Request;

class HabitAnalysisController extends Controller
{
    public function __construct(private HabitService $service) {}

    public function analyze(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasModuleFeature('habits', 'ai_analyzer'), 403);

        $request->validate([
            'goals' => 'required|string|max:2000',
        ]);

        $habits = $user->habits()->where('is_active', true)->get();

        // Collect stats for each habit
        $habitsData = $habits->map(function ($habit) {
            $stats = $this->service->getStats($habit);

            return [
                'name' => $habit->name,
                'type' => $habit->type,
                'unit' => $habit->unit,
                'target_value' => $habit->target_value,
                'frequency' => $habit->frequency,
                'routine' => $habit->routine ?? 'anytime',
                'current_streak' => $stats['current_streak'],
                'best_streak' => $stats['best_streak'],
                'rate_7' => $stats['rate_7'],
                'rate_30' => $stats['rate_30'],
                'day_of_week' => $stats['day_of_week'] ?? [],
                'average' => $stats['average'] ?? null,
            ];
        });

        $agent = new HabitAnalyzer($user, $habitsData->toJson(), $request->input('goals'));
        $response = $agent->prompt('Analiza mis hábitos y sugiere un plan basado en mis propósitos.');

        $analysis = json_decode($response->text, true);

        // Handle markdown-wrapped JSON
        if (!is_array($analysis)) {
            if (preg_match('/\{[\s\S]*\}/', $response->text, $matches)) {
                $analysis = json_decode($matches[0], true);
            }
        }

        if (!is_array($analysis)) {
            return response()->json(['error' => 'No se pudo procesar el análisis.'], 422);
        }

        return response()->json($analysis);
    }

    public function applySuggestions(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasModuleFeature('habits', 'ai_analyzer'), 403);

        $request->validate([
            'habits' => 'required|array|min:1',
            'habits.*.name' => 'required|string|max:255',
            'habits.*.type' => 'required|in:boolean,numeric',
            'habits.*.unit' => 'nullable|string|max:50',
            'habits.*.target_value' => 'nullable|numeric|min:0',
            'habits.*.frequency' => 'required|in:daily,weekly,custom',
            'habits.*.target_days' => 'nullable|array',
            'habits.*.routine' => 'nullable|in:morning,afternoon,evening,anytime',
            'habits.*.color' => 'nullable|string|max:20',
            'habits.*.replaces' => 'nullable|string|max:255',
        ]);

        $created = [];

        foreach ($request->input('habits') as $habitData) {
            // If replaces an existing habit, deactivate the old one
            if (!empty($habitData['replaces'])) {
                $user->habits()
                    ->where('name', $habitData['replaces'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            unset($habitData['replaces'], $habitData['description']);

            $created[] = $this->service->create($user, $habitData);
        }

        return response()->json([
            'message' => count($created) . ' hábitos creados.',
            'count' => count($created),
        ]);
    }
}
