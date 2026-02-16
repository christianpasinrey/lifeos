<?php

namespace App\Modules\CustomEntities\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CustomEntities\CustomEntityService;
use App\Modules\CustomEntities\Models\CustomModel;
use Illuminate\Http\Request;

class CustomEntityController extends Controller
{
    public function __construct(private CustomEntityService $service) {}

    public function index(Request $request)
    {
        return response()->json($this->service->getUserModels($request->user()));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required|string|max:255',
            'attributes.*.type' => 'required|in:string,text,number,decimal,boolean,date,datetime,select,multiselect,json',
            'attributes.*.options' => 'nullable|array',
            'attributes.*.is_required' => 'nullable|boolean',
            'attributes.*.default_value' => 'nullable|string',
        ]);

        $model = $this->service->createModel(
            $request->user(),
            $request->input('name'),
            $request->input('description', ''),
            $request->input('attributes', []),
            $request->input('icon'),
            $request->input('color'),
        );

        return response()->json($model, 201);
    }

    public function show(Request $request, CustomModel $customModel)
    {
        abort_unless($customModel->user_id === $request->user()->id, 403);
        return response()->json($customModel->load('attributes'));
    }

    public function entries(Request $request, CustomModel $customModel)
    {
        abort_unless($customModel->user_id === $request->user()->id, 403);
        return response()->json($this->service->getEntries($customModel, $request->user()));
    }

    public function storeEntry(Request $request, CustomModel $customModel)
    {
        abort_unless($customModel->user_id === $request->user()->id, 403);
        $request->validate(['data' => 'required|array']);
        $entry = $this->service->createEntry($customModel, $request->user(), $request->input('data'));
        return response()->json($entry, 201);
    }
}
