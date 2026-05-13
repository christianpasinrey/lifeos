<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\Models\Cycle;
use App\Modules\Tasks\TaskService;
use App\Services\TagService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateTaskTool extends Tool
{
    protected string $description = 'Creates a new task in a specific column with optional rich body HTML, custom field values, tags (existing IDs or new names), and an optional cycle assignment.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'column_id' => $schema->integer()->description('The ID of the column to create the task in')->required(),
            'title' => $schema->string()->description('Task title')->required(),
            'description' => $schema->string()->description('Short plain-text summary (legacy field, kept for back-compat)'),
            'body_html' => $schema->string()->description('Rich HTML body. Persisted as-is — sanitize on render. Use for full issue-style content (headings, lists, links, code blocks).'),
            'priority' => $schema->string()->description('Priority: low, medium (default), or high'),
            'due_date' => $schema->string()->description('Due date in YYYY-MM-DD format'),
            'cycle_id' => $schema->integer()->description('Optional cycle ID. Must belong to the same board as the column.'),
            'field_values' => $schema->object()->description('Optional custom field values: {field_id: value}'),
            'tag_ids' => $schema->array()->description('Optional array of existing tag IDs to attach'),
            'tag_names' => $schema->array()->description('Optional array of tag names — created if they do not exist for this user'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'column_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'body_html' => 'nullable|string|max:65535',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date_format:Y-m-d',
            'cycle_id' => 'nullable|integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
        ]);

        $column = Column::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->find($request->get('column_id'));

        if (! $column) {
            return Response::text('Error: Column not found or does not belong to you.');
        }

        if ($request->filled('cycle_id')) {
            $cycle = Cycle::where('id', $request->get('cycle_id'))
                ->where('board_id', $column->board_id)
                ->first();
            if (! $cycle) {
                return Response::text('Error: Cycle not found on this board.');
            }
        }

        $data = array_filter([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'body_html' => $request->get('body_html'),
            'priority' => $request->get('priority', 'medium'),
            'due_date' => $request->get('due_date'),
            'cycle_id' => $request->get('cycle_id'),
        ], fn ($v) => $v !== null);

        $task = app(TaskService::class)->createTask($column, $user, $data);

        if ($request->has('field_values') && is_array($request->get('field_values'))) {
            $boardId = $column->board_id;
            foreach ($request->get('field_values') as $fieldId => $value) {
                $field = \App\Modules\Tasks\Models\CustomField::where('id', $fieldId)
                    ->where('board_id', $boardId)->first();
                if (! $field) {
                    continue;
                }
                if ($field->type === 'multi_select' && is_array($value)) {
                    $value = json_encode($value);
                }
                \App\Modules\Tasks\Models\CustomFieldValue::create([
                    'task_id' => $task->id,
                    'custom_field_id' => $field->id,
                    'value' => $value,
                ]);
            }
        }

        $tagIds = $request->get('tag_ids', []) ?: [];
        $tagNames = $request->get('tag_names', []) ?: [];
        if (! empty($tagIds) || ! empty($tagNames)) {
            $service = app(TagService::class);
            $resolved = $service->resolveTagIds($user, $tagIds, $tagNames);
            if (! empty($resolved)) {
                $service->applyToTaggable($task, $resolved);
            }
        }

        $tags = $task->fresh()->tags->pluck('name')->join(', ');
        $tagsLine = $tags ? " [tags: {$tags}]" : '';

        return Response::text("Task '{$task->title}' created (ID: {$task->id}) in column '{$column->name}'{$tagsLine}.");
    }
}
