<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\Budget;
use Carbon\Carbon;

class BudgetService
{
    public function getBudgets(User $user, bool $activeOnly = true)
    {
        $query = $user->hasMany(Budget::class)->with('category')->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function createBudget(User $user, array $data): Budget
    {
        $data['user_id'] = $user->id;
        return Budget::create($data);
    }

    public function updateBudget(Budget $budget, array $data): Budget
    {
        $budget->update($data);
        return $budget->fresh('category');
    }

    public function deleteBudget(Budget $budget): void
    {
        $budget->delete();
    }

    public function getProgress(Budget $budget): array
    {
        [$start, $end] = $this->getPeriodRange($budget);

        $query = $budget->user->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$start, $end]);

        if ($budget->category_id) {
            $query->where('category_id', $budget->category_id);
        }

        $spent = (float) $query->sum('amount');
        $limit = (float) $budget->amount;
        $remaining = $limit - $spent;
        $percentage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;

        return [
            'budget_id' => $budget->id,
            'name' => $budget->name,
            'limit' => $limit,
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage' => $percentage,
            'over_budget' => $spent > $limit,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ];
    }

    public function getAllProgress(User $user): array
    {
        $budgets = $this->getBudgets($user);

        return $budgets->map(fn (Budget $b) => $this->getProgress($b))->toArray();
    }

    private function getPeriodRange(Budget $budget): array
    {
        $now = Carbon::now();

        return match ($budget->period) {
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'quarterly' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }
}
