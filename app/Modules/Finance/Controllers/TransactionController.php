<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\FinanceService;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private FinanceService $service) {}

    public function index(Request $request)
    {
        $transactions = $this->service->getTransactions(
            $request->user(),
            $request->get('type'),
            $request->get('category_id'),
            $request->get('start_date'),
            $request->get('end_date')
        );

        return response()->json(['data' => $transactions]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'category_id' => 'nullable|exists:finance_categories,id',
            'date' => 'nullable|date',
        ]);

        if (!empty($validated['category_id'])) {
            abort_unless(
                $request->user()->categories()->where('id', $validated['category_id'])->exists(),
                403,
                'La categoría seleccionada no pertenece al usuario.'
            );
        }

        $transaction = $this->service->createTransaction($request->user(), $validated);

        return response()->json(['data' => $transaction->load('category')], 201);
    }

    public function show(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        return response()->json(['data' => $transaction->load('category')]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'type' => 'sometimes|in:income,expense',
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
            'category_id' => 'nullable|exists:finance_categories,id',
            'date' => 'sometimes|date',
        ]);

        if (!empty($validated['category_id'])) {
            abort_unless(
                $request->user()->categories()->where('id', $validated['category_id'])->exists(),
                403,
                'La categoría seleccionada no pertenece al usuario.'
            );
        }

        $updated = $this->service->updateTransaction($transaction, $validated);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $this->service->deleteTransaction($transaction);

        return response()->json(['message' => 'Transacción eliminada']);
    }

    public function summary(Request $request)
    {
        $summary = $this->service->getSummary(
            $request->user(),
            $request->get('start_date'),
            $request->get('end_date')
        );

        return response()->json(['data' => $summary]);
    }
}
