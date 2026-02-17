<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\TaskService;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function __construct(private TaskService $service) {}

    public function store(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $column = $this->service->createColumn($board, $data);

        return response()->json(['data' => $column], 201);
    }

    public function update(Request $request, Column $column)
    {
        abort_unless($column->board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $column->update($data);

        return response()->json(['data' => $column]);
    }

    public function destroy(Request $request, Column $column)
    {
        abort_unless($column->board->user_id === $request->user()->id, 403);

        $column->delete();

        return response()->json(['message' => 'Columna eliminada']);
    }

    public function reorder(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:task_columns,id',
        ]);

        $this->service->reorderColumns($board, $data['order']);

        return response()->json(['message' => 'ok']);
    }
}
