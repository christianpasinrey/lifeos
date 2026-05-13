<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TagService
{
    /**
     * Find an existing tag by slug for the user, or create it.
     */
    public function findOrCreateByName(User $user, string $name, ?string $color = null): Tag
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            abort(422, "Invalid tag name: '{$name}'.");
        }

        $tag = Tag::firstOrNew(
            ['user_id' => $user->id, 'slug' => $slug],
            ['name' => $name, 'color' => $color ?? '#94a3b8'],
        );

        if (! $tag->exists) {
            $tag->save();
        }

        return $tag;
    }

    /**
     * Resolve a mixed list of tag IDs and tag names to existing Tag IDs.
     * Names auto-create. Foreign-owned IDs are silently dropped.
     *
     * @return array<int>
     */
    public function resolveTagIds(User $user, array $ids = [], array $names = []): array
    {
        $resolved = [];

        if (! empty($ids)) {
            $resolved = Tag::whereIn('id', $ids)
                ->where('user_id', $user->id)
                ->pluck('id')
                ->all();
        }

        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $resolved[] = $this->findOrCreateByName($user, $name)->id;
        }

        return array_values(array_unique($resolved));
    }

    /**
     * Attach (or sync, if replace=true) the given tag IDs to a model.
     */
    public function applyToTaggable(Model $taggable, array $tagIds, bool $replace = false): void
    {
        if ($replace) {
            $taggable->tags()->sync($tagIds);

            return;
        }

        $taggable->tags()->syncWithoutDetaching($tagIds);
    }

    public function detachFromTaggable(Model $taggable, array $tagIds): void
    {
        $taggable->tags()->detach($tagIds);
    }
}
