<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\Models\Cycle;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Requests\StoreTaskRequest;
use App\Modules\Tasks\TaskService;
use App\Services\TagService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $service,
        private TagService $tags,
    ) {}

    public function store(StoreTaskRequest $request, Column $column)
    {
        abort_unless($column->board->user_id === $request->user()->id, 403);

        if ($cid = $request->input('cycle_id')) {
            abort_unless(Cycle::where('id', $cid)->where('board_id', $column->board_id)->exists(),
                422, 'El cycle no pertenece a este tablero.');
        }

        $task = $this->service->createTask(
            $column,
            $request->user(),
            $request->persistedAttributes(),
        );

        $this->syncTagsFromRequest($task, $request);

        return response()->json(['data' => $this->serialize($task->fresh(['tags', 'cycle']))], 201);
    }

    public function update(StoreTaskRequest $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $data = $request->persistedAttributes();
        if ($request->has('cycle_id')) {
            $cid = $request->input('cycle_id');
            if ($cid !== null && (int) $cid !== 0) {
                abort_unless(
                    Cycle::where('id', $cid)->where('board_id', $task->column->board_id)->exists(),
                    422, 'El cycle no pertenece a este tablero.',
                );
                $data['cycle_id'] = (int) $cid;
            } else {
                $data['cycle_id'] = null;
            }
        }

        $task->update($data);

        if ($request->has('tag_ids') || $request->has('tag_names') || $request->has('replace_tags')) {
            $this->syncTagsFromRequest($task, $request);
        }

        return response()->json(['data' => $this->serialize($task->fresh(['tags', 'cycle']))]);
    }

    public function destroy(Request $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $task->delete();

        return response()->json(['message' => 'Tarea eliminada']);
    }

    public function show(Request $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $task->load('customFieldValues.field', 'media', 'tags', 'cycle');

        return response()->json(['data' => $this->serialize($task, full: true)]);
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

    private function syncTagsFromRequest(Task $task, StoreTaskRequest $request): void
    {
        $ids = (array) $request->input('tag_ids', []);
        $names = (array) $request->input('tag_names', []);
        $resolved = $this->tags->resolveTagIds($request->user(), $ids, $names);
        $this->tags->applyToTaggable($task, $resolved, (bool) $request->input('replace_tags', false));
    }

    private function serialize(Task $task, bool $full = false): array
    {
        $payload = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'body_html' => $task->body_html,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'priority' => $task->priority,
            'sort_order' => $task->sort_order,
            'column_id' => $task->column_id,
            'cycle_id' => $task->cycle_id,
            'cycle' => $task->relationLoaded('cycle') && $task->cycle ? [
                'id' => $task->cycle->id,
                'name' => $task->cycle->name,
                'color' => $task->cycle->color,
                'status' => $task->cycle->status,
            ] : null,
            'tags' => $task->relationLoaded('tags')
                ? $task->tags->map(fn ($t) => [
                    'id' => $t->id, 'name' => $t->name, 'slug' => $t->slug, 'color' => $t->color,
                ])->values()
                : [],
            'created_at' => $task->created_at,
        ];

        if ($full) {
            $payload['custom_field_values'] = $task->customFieldValues->map(fn ($v) => [
                'id' => $v->id,
                'field_id' => $v->custom_field_id,
                'field' => [
                    'id' => $v->field->id,
                    'name' => $v->field->name,
                    'type' => $v->field->type,
                    'options' => $v->field->options,
                    'required' => $v->field->required,
                ],
                'value' => $v->value,
            ]);
            $payload['attachments'] = $task->getMedia('task-attachments')->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->file_name,
                'url' => $m->getUrl(),
                'size' => $m->size,
                'mime_type' => $m->mime_type,
            ]);
        }

        return $payload;
    }
}
