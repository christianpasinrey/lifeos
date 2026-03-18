<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteBoardTool extends Tool
{
    protected string $description = 'Deletes a board and ALL its columns and tasks. This action is irreversible.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board to delete')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate(['board_id' => 'required|integer']);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->withCount(['columns', 'tasks'])
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $name = $board->name;
        $colCount = $board->columns_count;
        $taskCount = $board->tasks_count;
        $board->delete();

        return Response::text("Board '{$name}' deleted along with {$colCount} columns and {$taskCount} tasks.");
    }
}
