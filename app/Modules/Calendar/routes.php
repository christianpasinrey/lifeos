<?php

use App\Modules\Calendar\Controllers\CalendarController;
use App\Modules\Calendar\Controllers\CalendarEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:calendar'])->prefix('api/calendar')->group(function () {
    Route::get('events', [CalendarController::class, 'events']);
    Route::post('events', [CalendarEventController::class, 'store']);
    Route::put('events/{calendarEvent}', [CalendarEventController::class, 'update']);
    Route::delete('events/{calendarEvent}', [CalendarEventController::class, 'destroy']);
});
