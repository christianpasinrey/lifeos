<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\TaskService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateBoardTool extends Tool
{
    protected string $description = 'Creates a new Kanban board. Automatically creates 3 default columns: "Por hacer", "En curso", "Hecho".';

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Name for the new board')->required(),
            'description' => $schema->string()->description('Optional description for the board'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $board = app(TaskService::class)->createBoard($user, [
                'name' => $request->get('name'),
                'description' => $request->get('description'),
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return Response::text("Error: {$e->getMessage()}");
        }

        $columns = $board->columns->pluck('name')->implode(', ');

        return Response::text("Board '{$board->name}' created (ID: {$board->id}) with columns: {$columns}.");
    }
}
