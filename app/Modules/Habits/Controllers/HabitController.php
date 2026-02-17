<?php

namespace App\Modules\Habits\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Habits\HabitService;
use App\Modules\Habits\Models\Habit;
use App\Modules\Habits\Requests\StoreHabitRequest;
use App\Modules\Habits\Requests\UpdateHabitRequest;
use App\Modules\Habits\Resources\HabitResource;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function __construct(private HabitService $service) {}

    public function index(Request $request)
    {
        return HabitResource::collection($this->service->getAll($request->user()));
    }

    public function today(Request $request)
    {
        return HabitResource::collection($this->service->getToday($request->user()));
    }

    public function store(StoreHabitRequest $request)
    {
        $habit = $this->service->create($request->user(), $request->validated());
        return new HabitResource($habit);
    }

    public function show(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);
        $habit->load(['logs' => fn($q) => $q->where('completed_at', today())]);
        return new HabitResource($habit);
    }

    public function update(UpdateHabitRequest $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);
        $habit = $this->service->update($habit, $request->validated());
        return new HabitResource($habit);
    }

    public function destroy(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);
        $this->service->delete($habit);
        return response()->json(['message' => 'Hábito eliminado']);
    }

    public function sparklines(Request $request)
    {
        return response()->json($this->service->getSparklines($request->user()));
    }

    public function stats(Request $request, Habit $habit)
    {
        abort_unless($habit->user_id === $request->user()->id, 403);
        $stats = $this->service->getStats($habit);
        $habit->load(['logs' => fn($q) => $q->where('completed_at', today()), 'badges']);
        $stats['habit'] = new HabitResource($habit);
        return response()->json($stats);
    }
}
