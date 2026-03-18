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

        ## Typical Workflows
        - To set up a project: create-board → (columns created automatically) → create tasks in the first column
        - To reorganize: move-task between columns, reorder-columns
        - To get an overview: list-boards → get-board (shows all columns + tasks)
        - To track progress: move tasks from "Por hacer" → "En curso" → "Hecho"

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
    ];
}
