<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Cycle;
use App\Modules\Tasks\Models\Task;
use App\Services\TagService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateTaskTool extends Tool
{
    protected string $description = 'Updates a task: title, plain description, rich HTML body, priority, due date, cycle assignment, custom field values, and tags. Pass replace_tags=true to swap tags wholesale.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task to update')->required(),
            'title' => $schema->string()->description('New title'),
            'description' => $schema->string()->description('New plain description'),
            'body_html' => $schema->string()->description('New rich HTML body. Pass empty string to clear.'),
            'priority' => $schema->string()->description('New priority: low, medium, or high'),
            'due_date' => $schema->string()->description('New due date in YYYY-MM-DD format (empty string clears)'),
            'cycle_id' => $schema->integer()->description('Assign to cycle (must be on same board). Pass 0 to clear.'),
            'field_values' => $schema->object()->description('Optional custom field values: {field_id: value}'),
            'tag_ids' => $schema->array()->description('Tag IDs to attach (or sync, if replace_tags=true)'),
            'tag_names' => $schema->array()->description('Tag names — auto-created'),
            'replace_tags' => $schema->boolean()->description('If true, replace tags entirely; otherwise additive. Default: false.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'task_id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'body_html' => 'nullable|string|max:65535',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date_format:Y-m-d',
            'cycle_id' => 'nullable|integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
            'replace_tags' => 'nullable|boolean',
        ]);

        $task = Task::where('id', $request->get('task_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $task) {
            return Response::text('Error: Task not found or does not belong to you.');
        }

        $data = [];
        foreach (['title', 'description', 'body_html', 'priority', 'due_date'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->get($field) ?: null;
            }
        }

        if ($request->has('cycle_id')) {
            $cid = (int) $request->get('cycle_id');
            if ($cid === 0) {
                $data['cycle_id'] = null;
            } else {
                $cycle = Cycle::where('id', $cid)
                    ->where('board_id', $task->column->board_id)
                    ->first();
                if (! $cycle) {
                    return Response::text('Error: Cycle not found on this board.');
                }
                $data['cycle_id'] = $cid;
            }
        }

        $touchedTags = $request->has('tag_ids') || $request->has('tag_names') || $request->has('replace_tags');

        if (empty($data) && ! $request->has('field_values') && ! $touchedTags) {
            return Response::text('Error: Provide at least one field to update.');
        }

        if (! empty($data)) {
            $task->update($data);
        }

        if ($request->has('field_values') && is_array($request->get('field_values'))) {
            $boardId = $task->column->board_id;
            foreach ($request->get('field_values') as $fieldId => $value) {
                $field = \App\Modules\Tasks\Models\CustomField::where('id', $fieldId)
                    ->where('board_id', $boardId)->first();
                if (! $field) {
                    continue;
                }
                if ($field->type === 'multi_select' && is_array($value)) {
                    $value = json_encode($value);
                }
                \App\Modules\Tasks\Models\CustomFieldValue::updateOrCreate(
                    ['task_id' => $task->id, 'custom_field_id' => $field->id],
                    ['value' => $value],
                );
            }
        }

        if ($touchedTags) {
            $service = app(TagService::class);
            $resolved = $service->resolveTagIds(
                $user,
                $request->get('tag_ids', []) ?: [],
                $request->get('tag_names', []) ?: [],
            );
            $service->applyToTaggable($task, $resolved, (bool) $request->get('replace_tags', false));
        }

        $tags = $task->fresh()->tags->pluck('name')->join(', ');
        $tagsLine = $tags ? " [tags: {$tags}]" : '';

        return Response::text("Task '{$task->title}' updated (ID: {$task->id}){$tagsLine}.");
    }
}
