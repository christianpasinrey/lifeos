<?php

use App\Modules\Notes\Controllers\NoteController;
use App\Modules\Notes\Controllers\NoteFolderController;
use App\Modules\Notes\Controllers\NoteTagController;
use App\Modules\Notes\Controllers\NoteGraphController;
use App\Modules\Notes\Controllers\NoteLinkController;
use App\Modules\Notes\Controllers\DailyNoteController;
use App\Modules\Notes\Controllers\NoteExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'module:notes'])->prefix('api')->group(function () {
    // Notes CRUD + search
    Route::get('notes/search', [NoteController::class, 'search']);
    Route::get('notes/trash', [NoteController::class, 'trash']);
    Route::post('notes/{note}/restore', [NoteController::class, 'restore'])->withTrashed();
    Route::apiResource('notes', NoteController::class);

    // Folders
    Route::apiResource('note-folders', NoteFolderController::class)->except(['show']);
    Route::put('note-folders/reorder', [NoteFolderController::class, 'reorder']);

    // Tags
    Route::get('note-tags', [NoteTagController::class, 'index']);
    Route::delete('note-tags/{tag}', [NoteTagController::class, 'destroy']);

    // Links & Backlinks
    Route::get('notes/{note}/backlinks', [NoteLinkController::class, 'backlinks']);
    Route::get('notes/{note}/unlinked', [NoteLinkController::class, 'unlinked']);

    // Graph
    Route::get('notes-graph', [NoteGraphController::class, 'index']);

    // Daily note
    Route::post('notes/daily', [DailyNoteController::class, 'store']);

    // Export
    Route::get('notes/{note}/export', [NoteExportController::class, 'note']);
    Route::get('note-folders/{folder}/export', [NoteExportController::class, 'folder']);
    Route::get('notes-export', [NoteExportController::class, 'vault']);

    // Noteables (cross-module linking)
    Route::post('notes/{note}/link', [NoteController::class, 'linkEntity']);
    Route::delete('notes/{note}/link/{noteable}', [NoteController::class, 'unlinkEntity']);
});
