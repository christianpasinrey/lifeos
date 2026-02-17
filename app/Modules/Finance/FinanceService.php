<?php

namespace App\Modules\Finance;

use App\Models\User;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountService;
use App\Modules\Finance\Services\CategoryService;
use App\Modules\Finance\Services\FinanceSummaryService;
use App\Modules\Finance\Services\TransactionService;

/**
 * Thin facade for backward compatibility (AI tools, etc.).
 * Delegates to specialized sub-services.
 */
class FinanceService
{
    public function __construct(
        private TransactionService $transactionService,
        private CategoryService $categoryService,
        private FinanceSummaryService $summaryService,
        private AccountService $accountService,
    ) {}

    public function getSummary(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->summaryService->getSummary($user, $startDate, $endDate);
    }

    public function getTransactions(User $user, ?string $type = null, ?int $categoryId = null, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->transactionService->getTransactions($user, array_filter([
            'type' => $type,
            'category_id' => $categoryId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));
    }

    public function createTransaction(User $user, array $data): Transaction
    {
        $lines = $data['lines'] ?? null;
        unset($data['lines']);
        return $this->transactionService->createTransaction($user, $data, $lines);
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        $lines = $data['lines'] ?? null;
        unset($data['lines']);
        return $this->transactionService->updateTransaction($transaction, $data, $lines);
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        $this->transactionService->deleteTransaction($transaction);
    }

    public function getCategories(User $user, ?string $type = null)
    {
        return $this->categoryService->getCategories($user, $type);
    }

    public function createCategory(User $user, array $data): Category
    {
        return $this->categoryService->createCategory($user, $data);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        return $this->categoryService->updateCategory($category, $data);
    }

    public function deleteCategory(Category $category): void
    {
        $this->categoryService->deleteCategory($category);
    }

    public function getAnalysisForAi(User $user): string
    {
        return $this->summaryService->getAnalysisForAi($user);
    }
}
