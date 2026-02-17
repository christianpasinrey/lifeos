<?php

namespace App\Modules\Tasks\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'columns_count' => $this->whenCounted('columns'),
            'tasks_count' => $this->whenCounted('tasks'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'columns' => $this->whenLoaded('columns', fn () =>
                $this->columns->map(fn ($col) => [
                    'id' => $col->id,
                    'name' => $col->name,
                    'sort_order' => $col->sort_order,
                    'color' => $col->color,
                    'tasks' => $col->tasks->map(fn ($task) => [
                        'id' => $task->id,
                        'title' => $task->title,
                        'description' => $task->description,
                        'due_date' => $task->due_date?->format('Y-m-d'),
                        'priority' => $task->priority,
                        'sort_order' => $task->sort_order,
                    ]),
                ])
            ),
        ];
    }
}
