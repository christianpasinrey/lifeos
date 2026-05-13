<?php

namespace App\Mcp\Tools\Cycles;

use App\Modules\Tasks\CycleService;
use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateCycleTool extends Tool
{
    protected string $description = 'Creates a cycle on a board. Cycles are groups of tasks (sprints, milestones, releases). A task references at most one cycle via tasks.cycle_id.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('Board ID')->required(),
            'name' => $schema->string()->description('Cycle name (e.g. "Sprint 12", "v2.0", "Q3 milestone")')->required(),
            'description' => $schema->string()->description('Optional description / goal'),
            'color' => $schema->string()->description('Hex color (default #6366f1 indigo)'),
            'status' => $schema->string()->description('Status: planned (default), active, completed'),
            'starts_on' => $schema->string()->description('Start date YYYY-MM-DD'),
            'ends_on' => $schema->string()->description('End date YYYY-MM-DD'),
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
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:5000',
            'color' => 'nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'status' => 'nullable|in:planned,active,completed',
            'starts_on' => 'nullable|date_format:Y-m-d',
            'ends_on' => 'nullable|date_format:Y-m-d|after_or_equal:starts_on',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        try {
            $cycle = app(CycleService::class)->createCycle($board, $request->all());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return Response::text("Error: {$e->getMessage()}");
        }

        return Response::text("Cycle '{$cycle->name}' created (ID: {$cycle->id}, status: {$cycle->status}) on board '{$board->name}'.");
    }
}
