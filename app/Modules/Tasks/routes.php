<?php

use App\Modules\Tasks\Controllers\BoardController;
use App\Modules\Tasks\Controllers\ColumnController;
use App\Modules\Tasks\Controllers\QuickTaskController;
use App\Modules\Tasks\Controllers\TaskController;
use App\Modules\Tasks\Controllers\TaskGeneratorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:tasks', 'module:ai_coach'])->prefix('api')->group(function () {
    Route::post('tasks/ai/suggest', [TaskGeneratorController::class, 'suggest']);
    Route::post('tasks/ai/confirm', [TaskGeneratorController::class, 'confirm']);
});

Route::middleware(['api', 'auth:sanctum', 'module:tasks'])->prefix('api')->group(function () {
    // Quick task (from calendar)
    Route::post('tasks/quick', [QuickTaskController::class, 'store']);

    // Boards
    Route::apiResource('boards', BoardController::class);

    // Columns
    Route::post('boards/{board}/columns', [ColumnController::class, 'store']);
    Route::put('boards/{board}/columns/reorder', [ColumnController::class, 'reorder']);
    Route::put('columns/{column}', [ColumnController::class, 'update']);
    Route::delete('columns/{column}', [ColumnController::class, 'destroy']);

    // Tasks
    Route::post('columns/{column}/tasks', [TaskController::class, 'store']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    Route::put('tasks/{task}/move', [TaskController::class, 'move']);
});
