<?php

use App\Modules\Finance\Controllers\CategoryController;
use App\Modules\Finance\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:finance'])->prefix('api/finance')->group(function () {
    // Transactions
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::get('transactions/summary', [TransactionController::class, 'summary']);
    Route::post('transactions/auto-categorize', [TransactionController::class, 'autoCategorize']);
    Route::post('transactions/apply-categories', [TransactionController::class, 'applyCategories']);
    Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
    Route::put('transactions/{transaction}', [TransactionController::class, 'update']);
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

    // Categories
    Route::apiResource('categories', CategoryController::class)->except(['show']);
});
