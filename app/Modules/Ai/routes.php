<?php

use App\Modules\Ai\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:ai_coach'])->prefix('api/ai')->group(function () {
    Route::post('/chat', [AiChatController::class, 'chat']);
    Route::post('/chat/stream', [AiChatController::class, 'stream']);
});
