<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Requests\StoreTaskRequest;
use App\Modules\Tasks\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private TaskService $service) {}

    public function store(StoreTaskRequest $request, Column $column)
    {
        abort_unless($column->board->user_id === $request->user()->id, 403);

        $task = $this->service->createTask(
            $column,
            $request->user(),
            $request->validated(),
        );

        return response()->json(['data' => $task], 201);
    }

    public function update(StoreTaskRequest $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $task->update($request->validated());

        return response()->json(['data' => $task->fresh()]);
    }

    public function destroy(Request $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $task->delete();

        return response()->json(['message' => 'Tarea eliminada']);
    }

    public function move(Request $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'column_id' => 'required|integer|exists:task_columns,id',
            'sort_order' => 'required|integer|min:0',
        ]);

        $this->service->moveTask($task, $data['column_id'], $data['sort_order']);

        return response()->json(['data' => $task->fresh()]);
    }
}
