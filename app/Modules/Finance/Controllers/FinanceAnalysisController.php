<?php

namespace App\Modules\Finance\Controllers;

use App\Events\ActionRequested;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Agents\FinanceAnalyzer;
use App\Modules\Finance\Services\BudgetService;
use App\Modules\Finance\Services\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceAnalysisController extends Controller
{
    public function __construct(
        private FinanceSummaryService $summaryService,
        private BudgetService $budgetService,
    ) {}

    public function analyze(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Current period summary
        $currentSummary = $this->summaryService->getSummary($user, $startDate, $endDate);

        // Previous period (same duration, shifted back)
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end);
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days);
        $previousSummary = $this->summaryService->getSummary($user, $prevStart->toDateString(), $prevEnd->toDateString());

        // All transactions from the period
        $transactions = $user->transactions()
            ->with('category', 'account')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($tx) => [
                'date' => $tx->date->toDateString(),
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'description' => $tx->description,
                'category' => $tx->category?->name ?? 'Sin categoría',
                'account' => $tx->account?->name ?? '',
            ]);

        // Budget progress
        $budgets = $this->budgetService->getAllProgress($user);

        // Build data for agent
        $financialData = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'current_summary' => [
                'income' => $currentSummary['income'],
                'expenses' => $currentSummary['expenses'],
                'balance' => $currentSummary['balance'],
                'by_category' => $currentSummary['by_category'],
            ],
            'previous_period' => [
                'start' => $prevStart->toDateString(),
                'end' => $prevEnd->toDateString(),
                'income' => $previousSummary['income'],
                'expenses' => $previousSummary['expenses'],
                'balance' => $previousSummary['balance'],
            ],
            'transactions' => $transactions,
            'accounts' => $currentSummary['accounts'],
            'total_balance' => $currentSummary['total_balance'],
            'budgets' => $budgets,
        ];

        $agent = new FinanceAnalyzer($user, json_encode($financialData, JSON_UNESCAPED_UNICODE));
        $response = $agent->prompt('Analiza mis finanzas del periodo seleccionado y dame un informe detallado.');

        $analysis = json_decode($response->text, true);

        // Handle markdown-wrapped JSON
        if (!is_array($analysis)) {
            if (preg_match('/\{[\s\S]*\}/', $response->text, $matches)) {
                $analysis = json_decode($matches[0], true);
            }
        }

        if (!is_array($analysis)) {
            return response()->json(['error' => 'No se pudo procesar el análisis.'], 422);
        }

        return response()->json($analysis);
    }

    public function applyActions(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'actions' => 'required|array|min:1',
            'actions.*.type' => 'required|in:habit,task',
            'actions.*.title' => 'required|string|max:255',
            'actions.*.description' => 'nullable|string|max:1000',
        ]);

        $created = ['habits' => 0, 'tasks' => 0];

        foreach ($request->input('actions') as $action) {
            $event = new ActionRequested(
                user: $user,
                type: $action['type'],
                title: $action['title'],
                description: $action['description'] ?? null,
            );

            event($event);

            if (isset($event->results['habits'])) {
                $created['habits']++;
            }
            if (isset($event->results['tasks'])) {
                $created['tasks']++;
            }
        }

        $parts = [];
        if ($created['habits'] > 0) {
            $parts[] = $created['habits'] . ' hábito' . ($created['habits'] > 1 ? 's' : '');
        }
        if ($created['tasks'] > 0) {
            $parts[] = $created['tasks'] . ' tarea' . ($created['tasks'] > 1 ? 's' : '');
        }

        return response()->json([
            'message' => implode(' y ', $parts) . ' creados.',
            'created' => $created,
        ]);
    }
}
