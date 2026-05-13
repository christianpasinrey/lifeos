<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\CycleService;
use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Cycle;
use App\Modules\Tasks\Resources\CycleResource;
use Illuminate\Http\Request;

class CycleController extends Controller
{
    public function __construct(private CycleService $service) {}

    public function index(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $cycles = $board->cycles()
            ->withCount('tasks')
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('sort_order')
            ->get();

        return CycleResource::collection($cycles);
    }

    public function store(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:5000',
            'color' => 'nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'status' => 'nullable|in:planned,active,completed',
            'starts_on' => 'nullable|date_format:Y-m-d',
            'ends_on' => 'nullable|date_format:Y-m-d|after_or_equal:starts_on',
        ]);

        $cycle = $this->service->createCycle($board, $data);

        return new CycleResource($cycle);
    }

    public function update(Request $request, Cycle $cycle)
    {
        abort_unless($cycle->board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'description' => 'sometimes|nullable|string|max:5000',
            'color' => 'sometimes|nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'status' => 'sometimes|in:planned,active,completed',
            'starts_on' => 'sometimes|nullable|date_format:Y-m-d',
            'ends_on' => 'sometimes|nullable|date_format:Y-m-d',
        ]);

        $this->service->updateCycle($cycle, $data);

        return new CycleResource($cycle->fresh());
    }

    public function destroy(Request $request, Cycle $cycle)
    {
        abort_unless($cycle->board->user_id === $request->user()->id, 403);

        $cycle->delete();

        return response()->json(['message' => 'Cycle eliminado']);
    }

    public function tasks(Request $request, Cycle $cycle)
    {
        abort_unless($cycle->board->user_id === $request->user()->id, 403);

        $cycle->load(['tasks.column', 'tasks.tags']);

        return new CycleResource($cycle);
    }
}
