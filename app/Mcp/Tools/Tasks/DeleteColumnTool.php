<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Column;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteColumnTool extends Tool
{
    protected string $description = 'Deletes a column and ALL its tasks. This action is irreversible.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'column_id' => $schema->integer()->description('The ID of the column to delete')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate(['column_id' => 'required|integer']);

        $column = Column::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->withCount('tasks')
            ->find($request->get('column_id'));

        if (! $column) {
            return Response::text('Error: Column not found or does not belong to you.');
        }

        $name = $column->name;
        $taskCount = $column->tasks_count;
        $column->delete();

        return Response::text("Column '{$name}' deleted along with {$taskCount} tasks.");
    }
}
