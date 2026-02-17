<?php

use App\Modules\Storage\Controllers\DriveController;
use App\Modules\Storage\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:storage'])->prefix('api/storage')->group(function () {
    // Drive — all user files
    Route::get('files', [DriveController::class, 'index']);
    Route::delete('files/{media}', [DriveController::class, 'destroy']);
    Route::get('files/{media}/download', [DriveController::class, 'download']);

    // Transaction attachments
    Route::post('transactions/{transaction}/media', [MediaController::class, 'store']);
    Route::delete('transactions/{transaction}/media/{media}', [MediaController::class, 'destroy']);
    Route::get('transactions/{transaction}/media/{media}/download', [MediaController::class, 'download']);
});
