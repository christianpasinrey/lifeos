<?php

namespace App\Modules\Finance;

use App\Models\User;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use Carbon\Carbon;

class FinanceService
{
    public function getSummary(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();

        $transactions = $user->transactions()
            ->with('category', 'media')
            ->whereBetween('date', [$start, $end])
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $balance = $income - $expenses;

        $byCategory = $transactions->groupBy('category_id')->map(function ($items, $categoryId) {
            $category = $items->first()->category;
            return [
                'category' => $category ? $category->name : 'Sin categoría',
                'color' => $category ? $category->color : '#6b7280',
                'total' => $items->sum('amount'),
                'count' => $items->count(),
            ];
        })->values();

        return [
            'income' => (float) $income,
            'expenses' => (float) $expenses,
            'balance' => (float) $balance,
            'by_category' => $byCategory,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ];
    }

    public function getTransactions(User $user, ?string $type = null, ?int $categoryId = null, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $user->transactions()->with('category', 'media')->orderBy('date', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->get();
    }

    public function createTransaction(User $user, array $data): Transaction
    {
        $data['user_id'] = $user->id;
        $data['date'] = $data['date'] ?? now()->toDateString();

        return Transaction::create($data);
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh('category', 'media');
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        $transaction->delete();
    }

    public function getCategories(User $user, ?string $type = null)
    {
        $query = $user->categories()->orderBy('name');

        if ($type) {
            $query->where(function ($q) use ($type) {
                $q->where('type', $type)->orWhere('type', 'both');
            });
        }

        return $query->get();
    }

    public function createCategory(User $user, array $data): Category
    {
        return $user->categories()->create($data);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function deleteCategory(Category $category): void
    {
        // Set transactions to null category before deleting
        $category->transactions()->update(['category_id' => null]);
        $category->delete();
    }

    public function getAnalysisForAi(User $user): string
    {
        $currentMonth = $this->getSummary($user);
        $lastMonth = $this->getSummary($user, now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth());

        $output = "📊 **Resumen Financiero**\n\n";
        $output .= "**Mes actual ({$currentMonth['period']['start']} - {$currentMonth['period']['end']}):**\n";
        $output .= "  💰 Ingresos: €" . number_format($currentMonth['income'], 2) . "\n";
        $output .= "  💸 Gastos: €" . number_format($currentMonth['expenses'], 2) . "\n";
        $output .= "  📈 Balance: €" . number_format($currentMonth['balance'], 2) . "\n\n";

        $output .= "**Mes anterior:**\n";
        $output .= "  💰 Ingresos: €" . number_format($lastMonth['income'], 2) . "\n";
        $output .= "  💸 Gastos: €" . number_format($lastMonth['expenses'], 2) . "\n";
        $output .= "  📈 Balance: €" . number_format($lastMonth['balance'], 2) . "\n\n";

        if ($currentMonth['by_category']->isNotEmpty()) {
            $output .= "**Gastos por categoría (mes actual):**\n";
            foreach ($currentMonth['by_category'] as $cat) {
                $output .= "  - {$cat['category']}: €" . number_format($cat['total'], 2) . " ({$cat['count']} transacciones)\n";
            }
        }

        return $output;
    }
}
