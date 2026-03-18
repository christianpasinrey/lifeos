<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReorderColumnsTool extends Tool
{
    protected string $description = 'Reorders columns in a board. Provide all column IDs in the desired order.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board')->required(),
            'column_ids' => $schema->array()->description('Array of column IDs in the desired order')->required(),
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
            'column_ids' => 'required|array|min:1',
            'column_ids.*' => 'integer',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        app(TaskService::class)->reorderColumns($board, $request->get('column_ids'));

        return Response::text("Columns reordered in board '{$board->name}'.");
    }
}
