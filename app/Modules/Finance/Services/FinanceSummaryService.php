<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use Carbon\Carbon;

class FinanceSummaryService
{
    public function __construct(
        private AccountService $accountService,
    ) {}

    public function getSummary(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();

        $transactions = $user->transactions()
            ->with('category', 'media', 'account')
            ->whereBetween('date', [$start, $end])
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $balance = $income - $expenses;

        $byCategory = $transactions
            ->whereIn('type', ['income', 'expense'])
            ->groupBy('category_id')
            ->map(function ($items, $categoryId) {
                $category = $items->first()->category;
                return [
                    'category' => $category ? $category->name : 'Sin categoría',
                    'color' => $category ? $category->color : '#6b7280',
                    'total' => $items->sum('amount'),
                    'count' => $items->count(),
                ];
            })->values();

        $accounts = $this->accountService->getAccounts($user);
        $totalBalance = $accounts->sum('current_balance');

        return [
            'income' => (float) $income,
            'expenses' => (float) $expenses,
            'balance' => (float) $balance,
            'by_category' => $byCategory,
            'accounts' => $accounts->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'currency' => $a->currency,
                'current_balance' => (float) $a->current_balance,
                'color' => $a->color,
                'icon' => $a->icon,
            ])->values(),
            'total_balance' => (float) $totalBalance,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ];
    }

    public function getAnalysisForAi(User $user): string
    {
        $currentMonth = $this->getSummary($user);
        $lastMonth = $this->getSummary(
            $user,
            now()->subMonth()->startOfMonth()->toDateString(),
            now()->subMonth()->endOfMonth()->toDateString()
        );

        $output = "**Resumen Financiero**\n\n";
        $output .= "**Mes actual ({$currentMonth['period']['start']} - {$currentMonth['period']['end']}):**\n";
        $output .= "  Ingresos: EUR " . number_format($currentMonth['income'], 2) . "\n";
        $output .= "  Gastos: EUR " . number_format($currentMonth['expenses'], 2) . "\n";
        $output .= "  Balance: EUR " . number_format($currentMonth['balance'], 2) . "\n\n";

        $output .= "**Mes anterior:**\n";
        $output .= "  Ingresos: EUR " . number_format($lastMonth['income'], 2) . "\n";
        $output .= "  Gastos: EUR " . number_format($lastMonth['expenses'], 2) . "\n";
        $output .= "  Balance: EUR " . number_format($lastMonth['balance'], 2) . "\n\n";

        if (!empty($currentMonth['accounts'])) {
            $output .= "**Cuentas:**\n";
            foreach ($currentMonth['accounts'] as $account) {
                $output .= "  - {$account['name']} ({$account['type']}): EUR " . number_format($account['current_balance'], 2) . "\n";
            }
            $output .= "  Total: EUR " . number_format($currentMonth['total_balance'], 2) . "\n\n";
        }

        if ($currentMonth['by_category']->count() > 0) {
            $output .= "**Gastos por categoría (mes actual):**\n";
            foreach ($currentMonth['by_category'] as $cat) {
                $output .= "  - {$cat['category']}: EUR " . number_format($cat['total'], 2) . " ({$cat['count']} transacciones)\n";
            }
        }

        return $output;
    }
}
