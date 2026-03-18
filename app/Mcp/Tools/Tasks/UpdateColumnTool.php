<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Column;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateColumnTool extends Tool
{
    protected string $description = 'Renames a column.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'column_id' => $schema->integer()->description('The ID of the column to rename')->required(),
            'name' => $schema->string()->description('New name for the column')->required(),
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
            'name' => 'required|string|max:255',
        ]);

        $column = Column::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->find($request->get('column_id'));

        if (! $column) {
            return Response::text('Error: Column not found or does not belong to you.');
        }

        $column->update(['name' => $request->get('name')]);

        return Response::text("Column renamed to '{$column->name}' (ID: {$column->id}).");
    }
}
