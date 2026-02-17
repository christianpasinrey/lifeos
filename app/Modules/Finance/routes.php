<?php

use App\Modules\Finance\Controllers\AccountController;
use App\Modules\Finance\Controllers\BankConnectionController;
use App\Modules\Finance\Controllers\BudgetController;
use App\Modules\Finance\Controllers\CategoryController;
use App\Modules\Finance\Controllers\InvoiceController;
use App\Modules\Finance\Controllers\RecurringTransactionController;
use App\Modules\Finance\Controllers\TaxController;
use App\Modules\Finance\Controllers\TransactionController;
use App\Modules\Finance\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:finance'])->prefix('api/finance')->group(function () {
    // Transactions
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::get('transactions/summary', [TransactionController::class, 'summary']);
    Route::post('transactions/import-preview', [TransactionController::class, 'importPreview']);
    Route::post('transactions/import', [TransactionController::class, 'import']);
    Route::post('transactions/auto-categorize', [TransactionController::class, 'autoCategorize']);
    Route::post('transactions/apply-categories', [TransactionController::class, 'applyCategories']);
    Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
    Route::put('transactions/{transaction}', [TransactionController::class, 'update']);
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

    // Accounts
    Route::get('accounts', [AccountController::class, 'index']);
    Route::post('accounts', [AccountController::class, 'store']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::put('accounts/{account}', [AccountController::class, 'update']);
    Route::delete('accounts/{account}', [AccountController::class, 'destroy']);
    Route::post('accounts/{account}/recalculate', [AccountController::class, 'recalculate']);

    // Transfers
    Route::post('transfers', [TransferController::class, 'store']);

    // Taxes
    Route::get('taxes', [TaxController::class, 'index']);
    Route::post('taxes', [TaxController::class, 'store']);
    Route::put('taxes/{tax}', [TaxController::class, 'update']);
    Route::delete('taxes/{tax}', [TaxController::class, 'destroy']);
    Route::post('taxes/{tax}/rates', [TaxController::class, 'storeRate']);
    Route::put('taxes/rates/{taxRate}', [TaxController::class, 'updateRate']);
    Route::delete('taxes/rates/{taxRate}', [TaxController::class, 'destroyRate']);

    // Budgets
    Route::get('budgets', [BudgetController::class, 'index']);
    Route::post('budgets', [BudgetController::class, 'store']);
    Route::put('budgets/{budget}', [BudgetController::class, 'update']);
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy']);
    Route::get('budgets/progress', [BudgetController::class, 'progress']);

    // Recurring Transactions
    Route::get('recurring', [RecurringTransactionController::class, 'index']);
    Route::post('recurring', [RecurringTransactionController::class, 'store']);
    Route::get('recurring/{recurringTransaction}', [RecurringTransactionController::class, 'show']);
    Route::put('recurring/{recurringTransaction}', [RecurringTransactionController::class, 'update']);
    Route::delete('recurring/{recurringTransaction}', [RecurringTransactionController::class, 'destroy']);

    // Invoices
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
    Route::post('invoices/{invoice}/link-payment', [InvoiceController::class, 'linkPayment']);
    Route::post('invoices/{invoice}/unlink-payment/{transaction}', [InvoiceController::class, 'unlinkPayment']);
    Route::post('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);

    // Banking
    Route::get('banking/institutions', [BankConnectionController::class, 'institutions']);
    Route::post('banking/connections', [BankConnectionController::class, 'store']);
    Route::get('banking/connections', [BankConnectionController::class, 'index']);
    Route::get('banking/connections/{connection}', [BankConnectionController::class, 'show']);
    Route::delete('banking/connections/{connection}', [BankConnectionController::class, 'destroy']);
    Route::post('banking/connections/{connection}/sync', [BankConnectionController::class, 'sync']);
    Route::post('banking/connections/{connection}/reconnect', [BankConnectionController::class, 'reconnect']);

    // Categories
    Route::apiResource('categories', CategoryController::class)->except(['show']);
});

// Banking callback — web route (user is redirected back from bank, uses session auth)
Route::middleware(['web', 'auth'])->get('finance/banking/callback', [BankConnectionController::class, 'callback']);
