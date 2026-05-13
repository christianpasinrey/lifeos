<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;

class LifeOsServer extends Server
{
    protected string $name = 'LifeOS';
    protected string $version = '1.1.0';
    protected string $instructions = <<<'MARKDOWN'
        LifeOS - Personal Life Management Server.

        ## Data Model: Tasks
        - **Boards** contain **Columns** which contain **Tasks**
        - New boards are created with 3 default columns: "Por hacer", "En curso", "Hecho"
        - Tasks have: title, description (short plain summary), body_html (rich HTML body for issue-style content), priority (low/medium/high), due_date (YYYY-MM-DD)
        - Tasks are ordered within columns by sort_order
        - **Custom Fields**: Boards can define custom fields (types: text, number, date, select, multi_select, checkbox, url). Each task can have values for these fields.
        - **Tags**: Per-user, polymorphic via taggables pivot. Same Tag can be attached to multiple Boards and Tasks. Created with name + hex color.
        - **Cycles**: Per-board groupings (sprints, milestones, releases). A task can belong to at most one Cycle on its board. Status: planned / active / completed. Optional starts_on / ends_on dates.
        - **Attachments**: Tasks can have file attachments (max 20 per task, max 10MB each)

        ## Rich Content
        - description = short plain-text summary (max 5000 chars)
        - body_html = long-form HTML body (max 65535 chars). Use for full issue-style content: headings, lists, links, code blocks, etc. Renderers MUST sanitize.

        ## Typical Workflows
        - Set up a project: create-board → (columns auto-created) → create-tag-tool for labels → create-cycle-tool for sprint → create-task-tool with tag_names and cycle_id
        - Reorganize: move-task between columns, reorder-columns
        - Overview: list-boards → get-board (full state with cycles, tags, custom fields)
        - Track progress: move tasks from "Por hacer" → "En curso" → "Hecho"; mark cycle as completed when sprint closes
        - Sprint review: list-tasks-by-cycle-tool to see every task per cycle grouped by column
        - Customize: create-board → create-custom-field → create tasks with field_values
        - Tag things: create-tag-tool, then attach-tags-tool (board or task) or pass tag_names on create-task-tool / update-task-tool
        - Attach files: add-task-attachment-tool with base64 content

        ## Important
        - All data is scoped to the authenticated user
        - Board/column limits depend on user plan
        - Deleting a board deletes all its columns, tasks, cycles, and custom fields
        - Deleting a column deletes all its tasks
        - Deleting a tag detaches it from every board/task automatically
        - Deleting a cycle sets cycle_id=NULL on its tasks (tasks are kept)
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
        // Tag tools (polymorphic, per-user)
        \App\Mcp\Tools\Tags\ListTagsTool::class,
        \App\Mcp\Tools\Tags\CreateTagTool::class,
        \App\Mcp\Tools\Tags\UpdateTagTool::class,
        \App\Mcp\Tools\Tags\DeleteTagTool::class,
        \App\Mcp\Tools\Tags\AttachTagsTool::class,
        \App\Mcp\Tools\Tags\DetachTagsTool::class,
        // Cycle tools (per-board)
        \App\Mcp\Tools\Cycles\ListCyclesTool::class,
        \App\Mcp\Tools\Cycles\CreateCycleTool::class,
        \App\Mcp\Tools\Cycles\UpdateCycleTool::class,
        \App\Mcp\Tools\Cycles\DeleteCycleTool::class,
        \App\Mcp\Tools\Cycles\ListTasksByCycleTool::class,
        // Notes tools
        \App\Mcp\Tools\Notes\SearchNotesTool::class,
        \App\Mcp\Tools\Notes\GetNoteTool::class,
        \App\Mcp\Tools\Notes\CreateNoteTool::class,
        \App\Mcp\Tools\Notes\ListFoldersTool::class,
    ];
}
