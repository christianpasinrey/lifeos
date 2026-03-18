<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AddTaskAttachmentTool extends Tool
{
    protected string $description = 'Attaches a file to a task using base64-encoded content.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task')->required(),
            'filename' => $schema->string()->description('The filename for the attachment')->required(),
            'content_base64' => $schema->string()->description('Base64-encoded file content')->required(),
            'mime_type' => $schema->string()->description('MIME type of the file (e.g. image/png)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasModule('tasks')) {
            return Response::text('Error: Tasks module is not active for this user.');
        }

        $request->validate([
            'task_id' => 'required|integer',
            'filename' => 'required|string|max:255',
            'content_base64' => 'required|string',
            'mime_type' => 'nullable|string|max:255',
        ]);

        $task = Task::where('id', $request->get('task_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $task) {
            return Response::text('Error: Task not found or does not belong to you.');
        }

        if ($task->getMedia('task-attachments')->count() >= 20) {
            return Response::text('Error: Task already has the maximum of 20 attachments.');
        }

        $decoded = base64_decode($request->get('content_base64'), true);

        if ($decoded === false) {
            return Response::text('Error: Invalid base64-encoded content.');
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if (strlen($decoded) > $maxSize) {
            return Response::text('Error: File exceeds the 10MB size limit.');
        }

        $filename = $request->get('filename');
        $adder = $task->addMediaFromString($decoded)
            ->usingFileName($filename);

        if ($request->get('mime_type')) {
            $adder->withCustomProperties(['mime_type' => $request->get('mime_type')]);
        }

        $media = $adder->toMediaCollection('task-attachments');

        return Response::text("Attachment '{$filename}' added to task '{$task->title}' (attachment ID: {$media->id}).");
    }
}
