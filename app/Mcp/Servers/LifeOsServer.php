<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;

class LifeOsServer extends Server
{
    protected string $name = 'LifeOS';
    protected string $version = '1.0.0';
    protected string $instructions = <<<'MARKDOWN'
        LifeOS - Personal Life Management Server.

        ## Data Model: Tasks
        - **Boards** contain **Columns** which contain **Tasks**
        - New boards are created with 3 default columns: "Por hacer", "En curso", "Hecho"
        - Tasks have: title, description, priority (low/medium/high), due_date (YYYY-MM-DD)
        - Tasks are ordered within columns by sort_order
        - **Custom Fields**: Boards can define custom fields (types: text, number, date, select, multi_select, checkbox, url). Each task can have values for these fields.
        - **Attachments**: Tasks can have file attachments (max 20 per task, max 10MB each)

        ## Typical Workflows
        - To set up a project: create-board → (columns created automatically) → create tasks in the first column
        - To reorganize: move-task between columns, reorder-columns
        - To get an overview: list-boards → get-board (shows all columns + tasks)
        - To track progress: move tasks from "Por hacer" → "En curso" → "Hecho"
        - To customize a board: create-board → create-custom-field (define fields) → create tasks with field_values
        - To attach files: add-task-attachment with base64 content

        ## Important
        - All data is scoped to the authenticated user
        - Board/column limits depend on user plan
        - Deleting a board deletes all its columns and tasks
        - Deleting a column deletes all its tasks
    MARKDOWN;

    protected array $tools = [
        // Board read tools
        \App\Mcp\Tools\Tasks\ListBoardsTool::class,
        \App\Mcp\Tools\Tasks\GetBoardTool::class,
        // Board mutation tools
        \App\Mcp\Tools\Tasks\CreateBoardTool::class,
        \App\Mcp\Tools\Tasks\UpdateBoardTool::class,
        \App\Mcp\Tools\Tasks\DeleteBoardTool::class,
        // Column tools
        \App\Mcp\Tools\Tasks\CreateColumnTool::class,
        \App\Mcp\Tools\Tasks\UpdateColumnTool::class,
        \App\Mcp\Tools\Tasks\ReorderColumnsTool::class,
        \App\Mcp\Tools\Tasks\DeleteColumnTool::class,
        // Task tools
        \App\Mcp\Tools\Tasks\ListTasksTool::class,
        \App\Mcp\Tools\Tasks\CreateTaskTool::class,
        \App\Mcp\Tools\Tasks\UpdateTaskTool::class,
        \App\Mcp\Tools\Tasks\MoveTaskTool::class,
        \App\Mcp\Tools\Tasks\DeleteTaskTool::class,
        // Custom field tools
        \App\Mcp\Tools\Tasks\ListCustomFieldsTool::class,
        \App\Mcp\Tools\Tasks\CreateCustomFieldTool::class,
        \App\Mcp\Tools\Tasks\UpdateCustomFieldTool::class,
        \App\Mcp\Tools\Tasks\DeleteCustomFieldTool::class,
        \App\Mcp\Tools\Tasks\ReorderCustomFieldsTool::class,
        // Task field values & attachments
        \App\Mcp\Tools\Tasks\SetTaskFieldValuesTool::class,
        \App\Mcp\Tools\Tasks\AddTaskAttachmentTool::class,
        \App\Mcp\Tools\Tasks\DeleteTaskAttachmentTool::class,
        // Notes tools
        \App\Mcp\Tools\Notes\SearchNotesTool::class,
        \App\Mcp\Tools\Notes\GetNoteTool::class,
        \App\Mcp\Tools\Notes\CreateNoteTool::class,
        \App\Mcp\Tools\Notes\ListFoldersTool::class,
    ];
}
