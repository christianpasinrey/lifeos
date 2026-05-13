<?php

namespace App\Mcp\Tools\Cycles;

use App\Modules\Tasks\Models\Cycle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteCycleTool extends Tool
{
    protected string $description = 'Deletes a cycle. Tasks previously assigned to the cycle have their cycle_id set to NULL (they are NOT deleted).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'cycle_id' => $schema->integer()->description('Cycle ID')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate(['cycle_id' => 'required|integer']);

        $cycle = Cycle::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->find($request->get('cycle_id'));

        if (! $cycle) {
            return Response::text('Error: Cycle not found or does not belong to you.');
        }

        $name = $cycle->name;
        $detached = $cycle->tasks()->count();
        $cycle->delete();

        return Response::text("Cycle '{$name}' deleted. {$detached} task(s) had their cycle_id set to NULL.");
    }
}
