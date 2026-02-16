<?php

use Illuminate\Support\Facades\Route;

// Admin routes are loaded by AdminServiceProvider
// SPA catch-all (must exclude /admin)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!admin).*$');
