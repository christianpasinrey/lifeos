<?php

use App\Modules\Ai\Controllers\AiChatController;
use App\Modules\Ai\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:ai_coach'])->prefix('api/ai')->group(function () {
    Route::post('/chat', [AiChatController::class, 'chat']);
    Route::post('/chat/stream', [AiChatController::class, 'stream']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::patch('/conversations/{id}', [ConversationController::class, 'update']);
    Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);
});
