<?php

use App\Modules\Admin\Controllers\AdminAuthController;
use App\Modules\Admin\Controllers\AdminDashboardController;
use App\Modules\Admin\Controllers\AdminUserController;
use App\Modules\Admin\Middleware\RequireAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // Auth (public)
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login']);

    // Protected
    Route::middleware(['web', 'auth', RequireAdmin::class])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::get('/users/{user}/modules', [AdminUserController::class, 'modules'])->name('admin.users.modules');
        Route::post('/users/{user}/toggle-module', [AdminUserController::class, 'toggleModule'])->name('admin.users.toggle-module');
    });
});
