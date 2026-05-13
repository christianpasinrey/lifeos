<?php

namespace App\Mcp\Tools\Cycles;

use App\Modules\Tasks\CycleService;
use App\Modules\Tasks\Models\Cycle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateCycleTool extends Tool
{
    protected string $description = 'Updates a cycle. Set status=active to start the cycle, status=completed to close it. Renaming regenerates the slug.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'cycle_id' => $schema->integer()->description('Cycle ID')->required(),
            'name' => $schema->string()->description('New name'),
            'description' => $schema->string()->description('New description'),
            'color' => $schema->string()->description('New hex color'),
            'status' => $schema->string()->description('planned | active | completed'),
            'starts_on' => $schema->string()->description('YYYY-MM-DD or empty to clear'),
            'ends_on' => $schema->string()->description('YYYY-MM-DD or empty to clear'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'cycle_id' => 'required|integer',
            'name' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:5000',
            'color' => 'nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'status' => 'nullable|in:planned,active,completed',
            'starts_on' => 'nullable',
            'ends_on' => 'nullable',
        ]);

        $cycle = Cycle::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->find($request->get('cycle_id'));

        if (! $cycle) {
            return Response::text('Error: Cycle not found or does not belong to you.');
        }

        $data = [];
        foreach (['name', 'description', 'color', 'status'] as $f) {
            if ($request->has($f)) {
                $data[$f] = $request->get($f) ?: null;
            }
        }
        foreach (['starts_on', 'ends_on'] as $d) {
            if ($request->has($d)) {
                $val = $request->get($d);
                $data[$d] = $val !== '' ? $val : null;
            }
        }

        if (empty($data)) {
            return Response::text('Error: nothing to update.');
        }

        try {
            app(CycleService::class)->updateCycle($cycle, $data);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return Response::text("Error: {$e->getMessage()}");
        }

        return Response::text("Cycle '{$cycle->name}' updated (status: {$cycle->status}).");
    }
}
