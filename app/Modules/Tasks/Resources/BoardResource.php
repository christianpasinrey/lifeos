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
            'cycles_count' => $this->whenCounted('cycles'),
            'tags' => $this->whenLoaded('tags', fn () =>
                $this->tags->map(fn ($t) => [
                    'id' => $t->id, 'name' => $t->name, 'slug' => $t->slug, 'color' => $t->color,
                ])->values()
            ),
            'cycles' => $this->whenLoaded('cycles', fn () =>
                $this->cycles->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'color' => $c->color,
                    'status' => $c->status,
                    'starts_on' => $c->starts_on?->format('Y-m-d'),
                    'ends_on' => $c->ends_on?->format('Y-m-d'),
                    'sort_order' => $c->sort_order,
                    'tasks_count' => $c->tasks_count ?? null,
                ])
            ),
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
                        'body_html' => $task->body_html,
                        'due_date' => $task->due_date?->format('Y-m-d'),
                        'priority' => $task->priority,
                        'sort_order' => $task->sort_order,
                        'cycle_id' => $task->cycle_id,
                        'custom_field_values' => $task->relationLoaded('customFieldValues')
                            ? $task->customFieldValues->map(fn ($v) => [
                                'id' => $v->id,
                                'field_id' => $v->custom_field_id,
                                'value' => $v->value,
                            ]) : [],
                        'tags' => $task->relationLoaded('tags')
                            ? $task->tags->map(fn ($t) => [
                                'id' => $t->id, 'name' => $t->name, 'slug' => $t->slug, 'color' => $t->color,
                            ])->values()
                            : [],
                        'attachments_count' => $task->relationLoaded('media')
                            ? $task->getMedia('task-attachments')->count() : 0,
                    ]),
                ])
            ),
            'custom_fields' => $this->whenLoaded('customFields', fn () =>
                $this->customFields->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'type' => $f->type,
                    'options' => $f->options,
                    'required' => $f->required,
                    'sort_order' => $f->sort_order,
                ])
            ),
        ];
    }
}
