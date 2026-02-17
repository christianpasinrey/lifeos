<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\RecurringTransaction;
use App\Modules\Finance\Services\RecurringService;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function __construct(private RecurringService $service) {}

    public function index(Request $request)
    {
        $recurring = $this->service->getRecurring(
            $request->user(),
            $request->boolean('active_only', true)
        );

        return response()->json(['data' => $recurring]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'account_id' => 'nullable|exists:finance_accounts,id',
            'category_id' => 'nullable|exists:finance_categories,id',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval' => 'sometimes|integer|min:1',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'lines' => 'nullable|array',
            'lines.*.concept' => 'required_with:lines|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0.0001',
            'lines.*.unit_price' => 'required_with:lines|numeric',
            'lines.*.taxes' => 'nullable|array',
            'lines.*.taxes.*.tax_id' => 'required|exists:finance_taxes,id',
            'lines.*.taxes.*.tax_rate_id' => 'required|exists:finance_tax_rates,id',
        ]);

        $lines = $validated['lines'] ?? null;
        unset($validated['lines']);

        $recurring = $this->service->createRecurring($request->user(), $validated, $lines);

        return response()->json(['data' => $recurring], 201);
    }

    public function show(Request $request, RecurringTransaction $recurringTransaction)
    {
        abort_unless($recurringTransaction->user_id === $request->user()->id, 403);

        $nextDates = $this->service->getNextOccurrences($recurringTransaction);

        return response()->json([
            'data' => $recurringTransaction->load('account', 'category', 'lines.taxes'),
            'next_occurrences' => $nextDates,
        ]);
    }

    public function update(Request $request, RecurringTransaction $recurringTransaction)
    {
        abort_unless($recurringTransaction->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'type' => 'sometimes|in:income,expense',
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
            'account_id' => 'nullable|exists:finance_accounts,id',
            'category_id' => 'nullable|exists:finance_categories,id',
            'frequency' => 'sometimes|in:daily,weekly,monthly,yearly',
            'interval' => 'sometimes|integer|min:1',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
            'lines' => 'nullable|array',
            'lines.*.concept' => 'required_with:lines|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0.0001',
            'lines.*.unit_price' => 'required_with:lines|numeric',
            'lines.*.taxes' => 'nullable|array',
            'lines.*.taxes.*.tax_id' => 'required|exists:finance_taxes,id',
            'lines.*.taxes.*.tax_rate_id' => 'required|exists:finance_tax_rates,id',
        ]);

        $lines = array_key_exists('lines', $validated) ? $validated['lines'] : null;
        unset($validated['lines']);

        $updated = $this->service->updateRecurring($recurringTransaction, $validated, $lines);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, RecurringTransaction $recurringTransaction)
    {
        abort_unless($recurringTransaction->user_id === $request->user()->id, 403);

        $this->service->deleteRecurring($recurringTransaction);

        return response()->json(['message' => 'Transacción recurrente eliminada']);
    }
}
