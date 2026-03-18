<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Models\Task;
use Illuminate\Http\Request;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        abort_if($task->getMedia('task-attachments')->count() >= 20, 422, 'Máximo 20 adjuntos por tarea.');

        $media = $task->addMediaFromRequest('file')
            ->toMediaCollection('task-attachments');

        return response()->json([
            'data' => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
            ],
        ], 201);
    }

    public function destroy(Request $request, Task $task, int $mediaId)
    {
        abort_unless($task->column->board->user_id === $request->user()->id, 403);

        $media = $task->getMedia('task-attachments')->firstWhere('id', $mediaId);

        abort_unless($media, 404);

        $media->delete();

        return response()->json(['message' => 'Adjunto eliminado']);
    }
}
