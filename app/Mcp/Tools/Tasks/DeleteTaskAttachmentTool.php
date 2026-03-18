<?php

namespace App\Mcp\Tools\Tasks;

use App\Modules\Tasks\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteTaskAttachmentTool extends Tool
{
    protected string $description = 'Deletes an attachment from a task.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('The ID of the task')->required(),
            'attachment_id' => $schema->integer()->description('The ID of the attachment to delete')->required(),
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
            'attachment_id' => 'required|integer',
        ]);

        $task = Task::where('id', $request->get('task_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $task) {
            return Response::text('Error: Task not found or does not belong to you.');
        }

        $media = $task->getMedia('task-attachments')->firstWhere('id', $request->get('attachment_id'));

        if (! $media) {
            return Response::text('Error: Attachment not found on this task.');
        }

        $filename = $media->file_name;
        $media->delete();

        return Response::text("Attachment '{$filename}' deleted from task '{$task->title}'.");
    }
}
