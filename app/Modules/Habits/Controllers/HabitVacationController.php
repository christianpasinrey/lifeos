<?php

namespace App\Modules\Habits\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\Models\Habit;
use App\Modules\Habits\Models\HabitVacation;
use Illuminate\Http\Request;

class HabitVacationController extends Controller
{
    public function index(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        return response()->json(
            $habit->vacations()->orderBy('starts_at', 'desc')->get()
        );
    }

    public function store(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);
        $this->authorize('feature:habits.vacation_mode');

        $data = $request->validate([
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'reason' => 'nullable|string|max:255',
        ]);

        $vacation = $habit->vacations()->create($data);

        return response()->json($vacation, 201);
    }

    public function destroy(Request $request, Habit $habit, HabitVacation $vacation)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);
        abort_unless($vacation->habit_id === $habit->id, 403);

        $vacation->delete();

        return response()->json(['message' => 'Vacación eliminada']);
    }
}
