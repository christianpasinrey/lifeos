<?php

namespace App\Modules\CustomEntities;

use App\Models\User;
use App\Modules\CustomEntities\Models\CustomAttribute;
use App\Modules\CustomEntities\Models\CustomEntry;
use App\Modules\CustomEntities\Models\CustomModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CustomEntityService
{
    public function createModel(User $user, string $name, string $description = '', array $attributes = [], ?string $icon = null, ?string $color = null): CustomModel
    {
        $maxEntities = $user->getModuleLimit('custom_entities', 'max_entities');

        if ($maxEntities !== null) {
            $currentCount = CustomModel::where('user_id', $user->id)->where('is_active', true)->count();
            if ($currentCount >= $maxEntities) {
                abort(403, "Has alcanzado el límite de {$maxEntities} entidades en tu plan actual.");
            }
        }

        $model = CustomModel::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description,
            'icon' => $icon,
            'color' => $color ?? '#6366f1',
        ]);

        foreach ($attributes as $i => $attr) {
            $model->attributes()->create([
                'name' => $attr['name'],
                'slug' => Str::slug($attr['name']),
                'type' => $attr['type'] ?? 'string',
                'options' => $attr['options'] ?? null,
                'is_required' => $attr['is_required'] ?? false,
                'default_value' => $attr['default_value'] ?? null,
                'sort_order' => $i,
            ]);
        }

        return $model->load('attributes');
    }

    public function addAttribute(CustomModel $model, array $data): CustomAttribute
    {
        $maxSort = $model->attributes()->max('sort_order') ?? -1;

        return $model->attributes()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'type' => $data['type'] ?? 'string',
            'options' => $data['options'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'default_value' => $data['default_value'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);
    }

    public function createEntry(CustomModel $model, User $user, array $data): CustomEntry
    {
        return $model->entries()->create([
            'user_id' => $user->id,
            'data' => $data,
        ]);
    }

    public function getEntries(CustomModel $model, ?User $user = null)
    {
        $query = $model->entries();
        if ($user) {
            $query->where('user_id', $user->id);
        }
        return $query->latest()->get();
    }

    public function getUserModels(User $user)
    {
        return CustomModel::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('attributes')
            ->get();
    }

    public function describeForAi(User $user): string
    {
        $models = $this->getUserModels($user);
        if ($models->isEmpty()) {
            return "El usuario no tiene entidades personalizadas.";
        }

        $lines = ["Entidades personalizadas del usuario:"];
        foreach ($models as $model) {
            $attrs = $model->attributes->map(fn($a) => "{$a->name} ({$a->type})")->join(', ');
            $count = $model->entries()->count();
            $lines[] = "  - {$model->name}: {$attrs} ({$count} registros)";
        }

        return implode("\n", $lines);
    }
}
