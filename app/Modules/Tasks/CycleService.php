<?php

namespace App\Modules\Tasks;

use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Cycle;
use Illuminate\Support\Str;

class CycleService
{
    public function createCycle(Board $board, array $data): Cycle
    {
        $name = $data['name'];
        $slug = Str::slug($name);

        if ($slug === '') {
            abort(422, "Invalid cycle name: '{$name}'.");
        }

        $existing = $board->cycles()->where('slug', $slug)->exists();
        if ($existing) {
            abort(409, "A cycle with slug '{$slug}' already exists on this board.");
        }

        return $board->cycles()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#6366f1',
            'status' => $data['status'] ?? 'planned',
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'sort_order' => $board->cycles()->count(),
        ]);
    }

    public function updateCycle(Cycle $cycle, array $data): Cycle
    {
        if (array_key_exists('name', $data) && $data['name'] !== null) {
            $slug = Str::slug($data['name']);
            if ($slug === '') {
                abort(422, 'Invalid cycle name.');
            }
            $clash = $cycle->board->cycles()
                ->where('slug', $slug)
                ->where('id', '!=', $cycle->id)
                ->exists();
            if ($clash) {
                abort(409, "Another cycle already uses slug '{$slug}'.");
            }
            $data['slug'] = $slug;
        }

        $cycle->update($data);

        return $cycle;
    }
}
