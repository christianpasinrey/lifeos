<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Agents\TransactionCategorizer;
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

        return response()->json(['data' => $transaction->load('category', 'media')], 201);
    }

    public function show(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        return response()->json(['data' => $transaction->load('category', 'media')]);
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

    public function autoCategorize(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'transaction_ids' => 'nullable|array',
            'transaction_ids.*' => 'integer|exists:transactions,id',
        ]);

        $query = $user->transactions()->with('category');

        $ids = $request->input('transaction_ids');
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereNull('category_id');
        }

        $transactions = $query->orderBy('date', 'desc')->limit(50)->get();

        if ($transactions->isEmpty()) {
            return response()->json(['data' => [], 'message' => 'No hay transacciones para categorizar.']);
        }

        $categories = $user->categories()->get(['id', 'name', 'color', 'type']);

        $txData = $transactions->map(fn ($tx) => [
            'id' => $tx->id,
            'type' => $tx->type,
            'amount' => $tx->amount,
            'description' => $tx->description,
            'notes' => $tx->notes,
            'date' => $tx->date->toDateString(),
            'current_category' => $tx->category?->name,
        ]);

        $agent = new TransactionCategorizer(
            $user,
            $categories->toJson(),
            $txData->toJson(),
        );

        $response = $agent->prompt('Categoriza las transacciones proporcionadas.');

        $proposals = json_decode($response->text, true);

        if (!is_array($proposals)) {
            // Try extracting JSON from markdown code block
            if (preg_match('/\[[\s\S]*\]/', $response->text, $matches)) {
                $proposals = json_decode($matches[0], true);
            }
        }

        if (!is_array($proposals)) {
            return response()->json(['error' => 'No se pudo interpretar la respuesta del modelo.'], 422);
        }

        // Enrich proposals with transaction info for frontend display
        $txById = $transactions->keyBy('id');
        $catById = $categories->keyBy('id');

        foreach ($proposals as &$p) {
            $tx = $txById[$p['transaction_id']] ?? null;
            if ($tx) {
                $p['transaction_description'] = $tx->description;
                $p['transaction_amount'] = $tx->amount;
                $p['transaction_type'] = $tx->type;
                $p['transaction_date'] = $tx->date->toDateString();
            }
            if (!empty($p['category_id'])) {
                $cat = $catById[$p['category_id']] ?? null;
                $p['category_name'] = $cat?->name;
                $p['category_color'] = $cat?->color;
            }
        }

        return response()->json(['data' => $proposals]);
    }

    public function applyCategories(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.transaction_id' => 'required|integer|exists:transactions,id',
            'assignments.*.category_id' => 'nullable|integer',
            'assignments.*.new_category' => 'nullable|array',
            'assignments.*.new_category.name' => 'required_with:assignments.*.new_category|string|max:100',
            'assignments.*.new_category.color' => 'nullable|string|max:7',
            'assignments.*.new_category.type' => 'nullable|in:income,expense,both',
        ]);

        $createdCategories = [];
        $updated = 0;

        foreach ($request->input('assignments') as $assignment) {
            $tx = Transaction::where('id', $assignment['transaction_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$tx) continue;

            $categoryId = $assignment['category_id'] ?? null;

            // Create new category if needed
            if (!$categoryId && !empty($assignment['new_category'])) {
                $catName = $assignment['new_category']['name'];

                // Reuse if already created in this batch
                if (isset($createdCategories[$catName])) {
                    $categoryId = $createdCategories[$catName];
                } else {
                    $newCat = $this->service->createCategory($user, [
                        'name' => $catName,
                        'color' => $assignment['new_category']['color'] ?? '#6366f1',
                        'type' => $assignment['new_category']['type'] ?? 'both',
                    ]);
                    $categoryId = $newCat->id;
                    $createdCategories[$catName] = $categoryId;
                }
            }

            if ($categoryId) {
                $tx->update(['category_id' => $categoryId]);
                $updated++;
            }
        }

        return response()->json([
            'message' => "Se categorizaron {$updated} transacciones.",
            'new_categories' => count($createdCategories),
        ]);
    }
}
