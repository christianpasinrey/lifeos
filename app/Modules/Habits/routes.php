<?php

use App\Modules\Habits\Controllers\HabitAnalysisController;
use App\Modules\Habits\Controllers\HabitController;
use App\Modules\Habits\Controllers\HabitLogController;
use App\Modules\Habits\Controllers\HabitTemplateController;
use App\Modules\Habits\Controllers\HabitVacationController;
use App\Modules\Habits\Controllers\HabitWeekController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:habits'])->prefix('api')->group(function () {
    Route::get('/habits/today', [HabitController::class, 'today']);
    Route::get('/habits/sparklines', [HabitController::class, 'sparklines']);
    Route::get('/habits/week', [HabitWeekController::class, 'index']);
    Route::get('/habits/templates', [HabitTemplateController::class, 'index']);
    Route::post('/habits/templates/{template}/apply', [HabitTemplateController::class, 'apply']);
    Route::post('/habits/analyze', [HabitAnalysisController::class, 'analyze']);
    Route::post('/habits/apply-suggestions', [HabitAnalysisController::class, 'applySuggestions']);
    Route::apiResource('habits', HabitController::class);
    Route::post('/habits/{habit}/log', [HabitLogController::class, 'toggle']);
    Route::post('/habits/{habit}/log/{date}', [HabitLogController::class, 'toggleDate']);
    Route::get('/habits/{habit}/stats', [HabitController::class, 'stats']);
    Route::get('/habits/{habit}/vacations', [HabitVacationController::class, 'index']);
    Route::post('/habits/{habit}/vacations', [HabitVacationController::class, 'store']);
    Route::delete('/habits/{habit}/vacations/{vacation}', [HabitVacationController::class, 'destroy']);
});
