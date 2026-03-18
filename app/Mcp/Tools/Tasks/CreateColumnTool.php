<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateColumnTool extends Tool
{
    protected string $description = 'Creates a new column in a board.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board to add the column to')->required(),
            'name' => $schema->string()->description('Name for the new column')->required(),
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
            'name' => 'required|string|max:255',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        try {
            $column = app(TaskService::class)->createColumn($board, [
                'name' => $request->get('name'),
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return Response::text("Error: {$e->getMessage()}");
        }

        return Response::text("Column '{$column->name}' created (ID: {$column->id}) in board '{$board->name}'.");
    }
}
