<?php

namespace App\Modules\Tasks;

use App\Models\User;
use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Column;
use App\Modules\Tasks\Models\Task;

class TaskService
{
    public function createBoard(User $user, array $data): Board
    {
        $maxBoards = $user->getModuleLimit('tasks', 'max_boards');

        if ($maxBoards !== null && $user->boards()->count() >= $maxBoards) {
            abort(403, "Has alcanzado el límite de {$maxBoards} tableros en tu plan actual.");
        }

        $data['sort_order'] = $user->boards()->count();
        $board = $user->boards()->create($data);

        $defaults = [
            ['name' => 'Por hacer', 'sort_order' => 0, 'color' => '#60a5fa'],
            ['name' => 'En curso', 'sort_order' => 1, 'color' => '#fbbf24'],
            ['name' => 'Hecho', 'sort_order' => 2, 'color' => '#34d399'],
        ];

        $board->columns()->createMany($defaults);

        return $board->load('columns');
    }

    public function createColumn(Board $board, array $data): Column
    {
        $user = $board->user;
        $maxColumns = $user->getModuleLimit('tasks', 'max_columns_per_board');

        if ($maxColumns !== null && $board->columns()->count() >= $maxColumns) {
            abort(403, "Has alcanzado el límite de {$maxColumns} columnas por tablero.");
        }

        $data['sort_order'] = $board->columns()->count();

        return $board->columns()->create($data);
    }

    public function reorderColumns(Board $board, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $board->columns()->where('id', $id)->update(['sort_order' => $index]);
        }
    }

    public function createTask(Column $column, User $user, array $data): Task
    {
        $data['user_id'] = $user->id;
        $data['sort_order'] = $column->tasks()->count();

        return $column->tasks()->create($data);
    }

    public function moveTask(Task $task, int $targetColumnId, int $targetSort): void
    {
        $oldColumnId = $task->column_id;

        // Update the task's column and position
        $task->update([
            'column_id' => $targetColumnId,
            'sort_order' => $targetSort,
        ]);

        // Reindex old column if task moved to a different column
        if ($oldColumnId !== $targetColumnId) {
            $this->reindexColumn($oldColumnId);
        }

        // Reindex target column
        $this->reindexColumn($targetColumnId, $task->id, $targetSort);
    }

    private function reindexColumn(int $columnId, ?int $pinnedTaskId = null, ?int $pinnedSort = null): void
    {
        $tasks = Column::find($columnId)->tasks()
            ->orderBy('sort_order')
            ->get();

        if ($pinnedTaskId !== null) {
            $others = $tasks->where('id', '!=', $pinnedTaskId)->values();
            $pinned = $tasks->firstWhere('id', $pinnedTaskId);

            $others->splice(min($pinnedSort, $others->count()), 0, [$pinned]);
            $tasks = $others;
        }

        $tasks->each(fn (Task $t, int $i) => $t->update(['sort_order' => $i]));
    }
}
