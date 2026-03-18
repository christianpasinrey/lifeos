<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReorderCustomFieldsTool extends Tool
{
    protected string $description = 'Reorders custom fields in a board. Provide all field IDs in the desired order.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board')->required(),
            'field_ids' => $schema->array()->description('Array of custom field IDs in the desired order')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'board_id' => 'required|integer',
            'field_ids' => 'required|array|min:1',
            'field_ids.*' => 'integer',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        foreach ($request->get('field_ids') as $index => $fieldId) {
            $board->customFields()->where('id', $fieldId)->update(['sort_order' => $index]);
        }

        return Response::text("Custom fields reordered in board '{$board->name}'.");
    }
}
