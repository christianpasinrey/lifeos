<?php

namespace App\Modules\Habits\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HabitCalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasModuleFeature('habits', 'calendar_view'), 403);

        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $habits = $user->habits()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);

        $logs = HabitLog::whereIn('habit_id', $habits->pluck('id'))
            ->whereBetween('completed_at', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        // Group by date
        $calendar = [];
        foreach ($logs as $log) {
            $date = $log->completed_at->format('Y-m-d');
            $calendar[$date][] = [
                'habit_id' => $log->habit_id,
                'value' => $log->value,
            ];
        }

        return response()->json([
            'month' => $month,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'habits' => $habits->map(fn($h) => [
                'id' => $h->id,
                'name' => $h->name,
                'color' => $h->color,
            ]),
            'calendar' => $calendar,
        ]);
    }
}
