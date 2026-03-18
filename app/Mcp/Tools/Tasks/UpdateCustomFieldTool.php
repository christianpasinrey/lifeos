<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\CustomField;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateCustomFieldTool extends Tool
{
    protected string $description = 'Updates a custom field name, options, or required status. Type cannot be changed if field has values.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'field_id' => $schema->integer()->description('The ID of the custom field to update')->required(),
            'name' => $schema->string()->description('New field name'),
            'options' => $schema->object()->description('New field options'),
            'required' => $schema->boolean()->description('Whether the field is required'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'field_id' => 'required|integer',
            'name' => 'nullable|string|max:255',
            'options' => 'nullable|array',
            'required' => 'nullable|boolean',
        ]);

        $field = CustomField::whereHas('board', fn ($q) => $q->where('user_id', $user->id))
            ->find($request->get('field_id'));

        if (! $field) {
            return Response::text('Error: Custom field not found or does not belong to you.');
        }

        $data = [];
        if ($request->has('name')) {
            $data['name'] = $request->get('name');
        }
        if ($request->has('required')) {
            $data['required'] = $request->get('required');
        }
        if ($request->has('options')) {
            $data['options'] = $request->get('options');

            // Cascade-clear removed select option values
            if (isset($data['options']['choices']) && in_array($field->type, ['select', 'multi_select'])) {
                $newIds = collect($data['options']['choices'])->pluck('id')->all();
                $oldIds = collect($field->options['choices'] ?? [])->pluck('id')->all();
                $removed = array_diff($oldIds, $newIds);

                foreach ($removed as $rid) {
                    if ($field->type === 'select') {
                        $field->values()->where('value', $rid)->update(['value' => null]);
                    } else {
                        $field->values()->get()->each(function ($fv) use ($rid) {
                            $arr = json_decode($fv->value, true) ?? [];
                            $filtered = array_values(array_filter($arr, fn ($id) => $id !== $rid));
                            $fv->update(['value' => empty($filtered) ? null : json_encode($filtered)]);
                        });
                    }
                }
            }
        }

        if (empty($data)) {
            return Response::text('Error: Provide at least one field to update.');
        }

        $field->update($data);

        return Response::text("Custom field '{$field->name}' updated (ID: {$field->id}).");
    }
}
