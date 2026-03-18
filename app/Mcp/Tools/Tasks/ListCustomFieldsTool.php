<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListCustomFieldsTool extends Tool
{
    protected string $description = 'Lists custom fields defined for a board, including their type and options.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'board_id' => $schema->integer()->description('The ID of the board')->required(),
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
            ->first();

        if (! $board) {
            return Response::text('Error: Board not found or does not belong to you.');
        }

        $fields = $board->customFields;

        if ($fields->isEmpty()) {
            return Response::text("No custom fields defined for board '{$board->name}'.");
        }

        $output = "Custom fields for '{$board->name}':\n\n";

        foreach ($fields as $field) {
            $output .= "- [{$field->id}] {$field->name} ({$field->type})";
            if ($field->required) {
                $output .= " [required]";
            }
            $output .= "\n";

            if (in_array($field->type, ['select', 'multi_select']) && ! empty($field->options['choices'])) {
                $choiceLabels = collect($field->options['choices'])->pluck('label')->join(', ');
                $output .= "  Choices: {$choiceLabels}\n";
            }
        }

        return Response::text($output);
    }
}
