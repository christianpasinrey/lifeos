<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        return response()->json([
            'data' => $board->customFields,
        ]);
    }

    public function store(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,date,select,multi_select,checkbox,url',
            'options' => 'nullable|array',
            'required' => 'boolean',
        ]);

        $data['sort_order'] = $board->customFields()->count();

        $field = $board->customFields()->create($data);

        return response()->json(['data' => $field], 201);
    }

    public function update(Request $request, CustomField $field)
    {
        abort_unless($field->board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'options' => 'nullable|array',
            'required' => 'sometimes|boolean',
        ]);

        // Cascade-clear values for removed select/multi_select options
        if (isset($data['options']['choices']) && in_array($field->type, ['select', 'multi_select'])) {
            $newChoiceIds = collect($data['options']['choices'])->pluck('id')->all();
            $oldChoiceIds = collect($field->options['choices'] ?? [])->pluck('id')->all();
            $removedIds = array_diff($oldChoiceIds, $newChoiceIds);

            if (!empty($removedIds)) {
                foreach ($removedIds as $removedId) {
                    if ($field->type === 'select') {
                        $field->values()->where('value', $removedId)->update(['value' => null]);
                    } else {
                        $field->values()->get()->each(function ($fv) use ($removedId) {
                            $arr = json_decode($fv->value, true) ?? [];
                            $filtered = array_values(array_filter($arr, fn ($id) => $id !== $removedId));
                            $fv->update(['value' => empty($filtered) ? null : json_encode($filtered)]);
                        });
                    }
                }
            }
        }

        $field->update($data);

        return response()->json(['data' => $field->fresh()]);
    }

    public function destroy(Request $request, CustomField $field)
    {
        abort_unless($field->board->user_id === $request->user()->id, 403);

        $field->delete();

        return response()->json(['message' => 'Campo eliminado']);
    }

    public function reorder(Request $request, Board $board)
    {
        abort_unless($board->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer',
        ]);

        foreach ($data['order'] as $index => $id) {
            $board->customFields()->where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Campos reordenados']);
    }
}
