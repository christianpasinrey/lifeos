<?php

namespace App\Modules\Habits\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\HabitService;
use App\Modules\Habits\Models\Habit;
use Illuminate\Http\Request;

class HabitLogController extends Controller
{
    public function __construct(private HabitService $service) {}

    public function toggle(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        if ($habit->type === 'numeric') {
            $request->validate(['value' => 'required|numeric|min:0']);
        }

        $result = $this->service->toggle(
            $habit,
            $request->input('value'),
            $request->input('notes'),
            $request->input('date'),
        );

        return response()->json($result);
    }
}
