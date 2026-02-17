<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\FinanceService;
use App\Modules\Finance\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private FinanceService $service) {}

    public function index(Request $request)
    {
        $categories = $this->service->getCategories(
            $request->user(),
            $request->get('type')
        );

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'sometimes|string|max:7',
            'type' => 'required|in:income,expense,both',
        ]);

        $category = $this->service->createCategory($request->user(), $validated);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:7',
            'type' => 'sometimes|in:income,expense,both',
        ]);

        $updated = $this->service->updateCategory($category, $validated);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $this->service->deleteCategory($category);

        return response()->json(['message' => 'Categoría eliminada']);
    }
}
