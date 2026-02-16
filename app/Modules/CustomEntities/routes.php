<?php

use App\Modules\CustomEntities\Controllers\CustomEntityController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('api')->group(function () {
    Route::get('/custom-models', [CustomEntityController::class, 'index']);
    Route::post('/custom-models', [CustomEntityController::class, 'store']);
    Route::get('/custom-models/{customModel}', [CustomEntityController::class, 'show']);
    Route::get('/custom-models/{customModel}/entries', [CustomEntityController::class, 'entries']);
    Route::post('/custom-models/{customModel}/entries', [CustomEntityController::class, 'storeEntry']);
});
