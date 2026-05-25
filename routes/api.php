<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Auth routes (the module routes are loaded by each ServiceProvider)
Route::post('/login', [AuthController::class, 'login']);

// Public self-service registration. Disabled by default; enable with REGISTER_ROUTE=true.
if (config('auth.registration_enabled')) {
    Route::post('/register', [AuthController::class, 'register']);
}

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/profile/mcp-token/status', [ProfileController::class, 'mcpTokenStatus']);
    Route::post('/profile/mcp-token', [ProfileController::class, 'generateMcpToken']);
    Route::delete('/profile/mcp-token', [ProfileController::class, 'revokeMcpToken']);
});
