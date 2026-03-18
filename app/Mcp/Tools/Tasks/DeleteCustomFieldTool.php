<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\CustomField;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteCustomFieldTool extends Tool
{
    protected string $description = 'Deletes a custom field and all its values across all tasks. This action is irreversible.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'field_id' => $schema->integer()->description('The ID of the custom field to delete')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate(['field_id' => 'required|integer']);

        $field = CustomField::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->find($request->get('field_id'));

        if (! $field) {
            return Response::text('Error: Custom field not found or does not belong to you.');
        }

        $name = $field->name;
        $valuesCount = $field->values()->count();
        $field->values()->delete();
        $field->delete();

        return Response::text("Custom field '{$name}' deleted along with {$valuesCount} value(s).");
    }
}
