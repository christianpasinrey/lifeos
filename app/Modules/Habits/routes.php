<?php

use App\Modules\Habits\Controllers\HabitController;
use App\Modules\Habits\Controllers\HabitLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:habits'])->prefix('api')->group(function () {
    Route::get('/habits/today', [HabitController::class, 'today']);
    Route::apiResource('habits', HabitController::class);
    Route::post('/habits/{habit}/log', [HabitLogController::class, 'toggle']);
    Route::get('/habits/{habit}/stats', [HabitController::class, 'stats']);
});
