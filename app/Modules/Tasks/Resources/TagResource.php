<?php

namespace App\Modules\Tasks\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'description' => $this->description,
            'boards_count' => $this->when(isset($this->boards_count), $this->boards_count),
            'tasks_count' => $this->when(isset($this->tasks_count), $this->tasks_count),
            'created_at' => $this->created_at,
        ];
    }
}
