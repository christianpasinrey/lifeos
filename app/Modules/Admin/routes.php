<?php

use App\Modules\Admin\Controllers\AdminApiController;
use App\Modules\Admin\Middleware\RequireAdmin;
use Illuminate\Support\Facades\Route;

// API routes for admin SPA
Route::middleware(['api', 'auth:sanctum', RequireAdmin::class])
    ->prefix('api/admin')
    ->group(function () {
        Route::get('/stats', [AdminApiController::class, 'stats']);
        Route::get('/users', [AdminApiController::class, 'users']);
        Route::get('/users/{user}', [AdminApiController::class, 'user']);
        Route::post('/users/{user}/toggle-module', [AdminApiController::class, 'toggleModule']);
        Route::put('/users/{user}/plan', [AdminApiController::class, 'updatePlan']);
        Route::get('/modules', [AdminApiController::class, 'modules']);
    });

// SPA catch-all — serves the Vue app for all /admin/* routes
Route::middleware('web')
    ->get('/admin/{any?}', fn () => view('app'))
    ->where('any', '.*');
