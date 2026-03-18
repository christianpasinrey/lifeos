<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateCustomFieldTool extends Tool
{
    protected string $description = 'Creates a new custom field for a board.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board')->required(),
            'name' => $schema->string()->description('Field name')->required(),
            'type' => $schema->string()->description('Field type: text, number, date, select, multi_select, checkbox, url')->required(),
            'options' => $schema->object()->description('Field options (e.g. {"choices": [{"id": "opt1", "label": "Option 1"}]} for select/multi_select)'),
            'required' => $schema->boolean()->description('Whether the field is required (default: false)'),
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
            'type' => 'required|in:text,number,date,select,multi_select,checkbox,url',
            'options' => 'nullable|array',
            'required' => 'nullable|boolean',
        ]);

        $board = Board::where('id', $request->get('board_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $field = $board->customFields()->create([
            'name' => $request->get('name'),
            'type' => $request->get('type'),
            'options' => $request->get('options', []),
            'required' => $request->get('required', false),
            'sort_order' => $board->customFields()->count(),
        ]);

        return Response::text("Custom field '{$field->name}' ({$field->type}) created (ID: {$field->id}) on board '{$board->name}'.");
    }
}
