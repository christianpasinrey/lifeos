<?php

use App\Modules\Tasks\Controllers\BoardController;
use App\Modules\Tasks\Controllers\ColumnController;
use App\Modules\Tasks\Controllers\QuickTaskController;
use App\Modules\Tasks\Controllers\CustomFieldController;
use App\Modules\Tasks\Controllers\TaskAttachmentController;
use App\Modules\Tasks\Controllers\TaskController;
use App\Modules\Tasks\Controllers\TaskFieldValueController;
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

    // Task show (single task with full details)
    Route::get('tasks/{task}', [TaskController::class, 'show']);

    // Custom fields
    Route::get('boards/{board}/custom-fields', [CustomFieldController::class, 'index']);
    Route::post('boards/{board}/custom-fields', [CustomFieldController::class, 'store']);
    Route::put('boards/{board}/custom-fields/reorder', [CustomFieldController::class, 'reorder']);
    Route::put('custom-fields/{field}', [CustomFieldController::class, 'update']);
    Route::delete('custom-fields/{field}', [CustomFieldController::class, 'destroy']);

    // Task field values
    Route::put('tasks/{task}/field-values', [TaskFieldValueController::class, 'update']);

    // Task attachments
    Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
    Route::delete('tasks/{task}/attachments/{media}', [TaskAttachmentController::class, 'destroy']);
});
