<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Services\BudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(private BudgetService $service) {}

    public function index(Request $request)
    {
        $budgets = $this->service->getBudgets(
            $request->user(),
            $request->boolean('active_only', true)
        );

        return response()->json(['data' => $budgets]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'category_id' => 'nullable|exists:finance_categories,id',
            'is_active' => 'sometimes|boolean',
            'color' => 'nullable|string|max:7',
        ]);

        $budget = $this->service->createBudget($request->user(), $validated);

        return response()->json(['data' => $budget->load('category')], 201);
    }

    public function update(Request $request, Budget $budget)
    {
        abort_unless($budget->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'period' => 'sometimes|in:monthly,quarterly,yearly',
            'start_date' => 'sometimes|date',
            'category_id' => 'nullable|exists:finance_categories,id',
            'is_active' => 'sometimes|boolean',
            'color' => 'nullable|string|max:7',
        ]);

        $updated = $this->service->updateBudget($budget, $validated);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Budget $budget)
    {
        abort_unless($budget->user_id === $request->user()->id, 403);

        $this->service->deleteBudget($budget);

        return response()->json(['message' => 'Presupuesto eliminado']);
    }

    public function progress(Request $request)
    {
        $progress = $this->service->getAllProgress($request->user());

        return response()->json(['data' => $progress]);
    }
}
