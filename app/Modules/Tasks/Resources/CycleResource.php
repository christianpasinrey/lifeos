<?php

namespace App\Modules\Tasks\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'starts_on' => $this->starts_on?->format('Y-m-d'),
            'ends_on' => $this->ends_on?->format('Y-m-d'),
            'sort_order' => $this->sort_order,
            'tasks_count' => $this->when(isset($this->tasks_count), $this->tasks_count),
            'tasks' => $this->whenLoaded('tasks', fn () =>
                $this->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'priority' => $t->priority,
                    'column_id' => $t->column_id,
                    'due_date' => $t->due_date?->format('Y-m-d'),
                ])
            ),
            'created_at' => $this->created_at,
        ];
    }
}
