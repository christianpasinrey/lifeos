<?php

namespace App\Modules\Habits\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\Models\Habit;
use App\Modules\Habits\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HabitWeekController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasModuleFeature('habits', 'weekly_view'), 403);

        $start = $request->input('start')
            ? Carbon::parse($request->input('start'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        $end = $start->copy()->addDays(6);
        $dates = collect(range(0, 6))->map(fn($i) => $start->copy()->addDays($i));

        $habits = $user->habits()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $logs = HabitLog::whereIn('habit_id', $habits->pluck('id'))
            ->whereBetween('completed_at', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->groupBy('habit_id');

        $matrix = $habits->map(function (Habit $habit) use ($dates, $logs) {
            $habitLogs = $logs->get($habit->id, collect())->keyBy(fn($l) => $l->completed_at->format('Y-m-d'));

            return [
                'id' => $habit->id,
                'name' => $habit->name,
                'color' => $habit->color,
                'type' => $habit->type,
                'unit' => $habit->unit,
                'target_value' => $habit->target_value,
                'current_streak' => $habit->current_streak,
                'days' => $dates->map(function ($date) use ($habit, $habitLogs) {
                    $dateStr = $date->format('Y-m-d');
                    $log = $habitLogs->get($dateStr);
                    $due = $habit->isDueOnDate($date);

                    return [
                        'date' => $dateStr,
                        'due' => $due,
                        'completed' => (bool) $log,
                        'value' => $log?->value,
                        'notes' => $log?->notes,
                        'is_future' => $date->isAfter(now()->endOfDay()),
                    ];
                })->values()->all(),
            ];
        });

        return response()->json([
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'dates' => $dates->map(fn($d) => $d->format('Y-m-d'))->values()->all(),
            'habits' => $matrix->values()->all(),
        ]);
    }
}
