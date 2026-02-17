<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickTaskController extends Controller
{
    public function __construct(private TaskService $service) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ]);

        $user = $request->user();

        // Find first board or create a default one
        $board = Board::where('user_id', $user->id)->orderBy('sort_order')->first();

        if (!$board) {
            $board = $this->service->createBoard($user, [
                'name' => 'Mis Tareas',
                'description' => 'Tablero predeterminado',
            ]);
        }

        // Find first column or create a default one
        $column = $board->columns()->orderBy('sort_order')->first();

        if (!$column) {
            $column = $this->service->createColumn($board, [
                'name' => 'Por hacer',
            ]);
        }

        $task = $this->service->createTask($column, $user, $data);

        return response()->json(['data' => $task], 201);
    }
}
