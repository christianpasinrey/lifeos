<?php

namespace App\Modules\Habits\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HabitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $todayLog = $this->logs->first();

        $completed = false;
        if ($this->type === 'numeric') {
            $completed = $todayLog && $this->target_value && $todayLog->value >= $this->target_value;
        } else {
            $completed = (bool) $todayLog;
        }

        $progress = null;
        if ($this->type === 'numeric' && $this->target_value && $todayLog) {
            $progress = min(100, round(($todayLog->value / $this->target_value) * 100));
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'type' => $this->type,
            'unit' => $this->unit,
            'target_value' => $this->target_value,
            'frequency' => $this->frequency,
            'target_days' => $this->target_days,
            'current_streak' => $this->current_streak,
            'best_streak' => $this->best_streak,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'completed_today' => $completed,
            'today_value' => $todayLog?->value,
            'progress' => $progress,
            'created_at' => $this->created_at,
        ];
    }
}
