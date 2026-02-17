<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Agents\TaskGenerator;
use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\TaskService;
use Illuminate\Http\Request;

class TaskGeneratorController extends Controller
{
    public function __construct(private TaskService $service) {}

    /**
     * Generate task suggestions from a text prompt via AI.
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:2000',
        ]);

        $agent = new TaskGenerator();
        $response = $agent->prompt($request->input('prompt'));

        $tasks = json_decode($response->text, true);

        if (!is_array($tasks)) {
            // Try to extract JSON from markdown code blocks
            if (preg_match('/\[.*\]/s', $response->text, $matches)) {
                $tasks = json_decode($matches[0], true);
            }
        }

        if (!is_array($tasks)) {
            return response()->json(['error' => 'No se pudieron generar tareas.'], 422);
        }

        // Sanitize
        $tasks = array_map(fn ($t) => [
            'title' => substr($t['title'] ?? '', 0, 255),
            'priority' => in_array($t['priority'] ?? '', ['low', 'medium', 'high']) ? $t['priority'] : 'medium',
        ], array_slice($tasks, 0, 15));

        return response()->json(['data' => $tasks]);
    }

    /**
     * Batch-create validated tasks into a column.
     */
    public function confirm(Request $request)
    {
        $data = $request->validate([
            'column_id' => 'required|integer|exists:task_columns,id',
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.priority' => 'nullable|in:low,medium,high',
        ]);

        $column = Column::findOrFail($data['column_id']);
        abort_unless($column->board->user_id === $request->user()->id, 403);

        $created = [];
        foreach ($data['tasks'] as $taskData) {
            $created[] = $this->service->createTask($column, $request->user(), $taskData);
        }

        return response()->json(['data' => $created], 201);
    }
}
