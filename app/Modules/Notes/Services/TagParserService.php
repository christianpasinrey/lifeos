<?php

namespace App\Modules\Notes\Services;

use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\NoteTag;

class TagParserService
{
    public function parse(Note $note): void
    {
        $content = $note->content ?? '';
        $userId = $note->user_id;

        // Match #tag and #parent/child (not inside code blocks or URLs)
        preg_match_all('/(?<!\w)#([\w\-]+(?:\/[\w\-]+)*)/', $content, $matches);

        $tagPaths = array_unique($matches[1] ?? []);
        $tagIds = [];

        foreach ($tagPaths as $path) {
            $tag = $this->findOrCreateTag($userId, $path);
            $tagIds[] = $tag->id;
        }

        // Also include tags from properties
        $propTags = $note->properties['tags'] ?? [];
        foreach ($propTags as $propTag) {
            $tag = $this->findOrCreateTag($userId, $propTag);
            $tagIds[] = $tag->id;
        }

        $note->tags()->sync(array_unique($tagIds));
    }

    private function findOrCreateTag(int $userId, string $fullPath): NoteTag
    {
        $existing = NoteTag::where('user_id', $userId)
            ->where('full_path', $fullPath)
            ->first();

        if ($existing) return $existing;

        $parts = explode('/', $fullPath);
        $parentId = null;
        $builtPath = '';
        $tag = null;

        foreach ($parts as $i => $part) {
            $builtPath = $i === 0 ? $part : "{$builtPath}/{$part}";

            $tag = NoteTag::firstOrCreate(
                ['user_id' => $userId, 'full_path' => $builtPath],
                ['name' => $part, 'parent_id' => $parentId],
            );

            $parentId = $tag->id;
        }

        return $tag;
    }
}
