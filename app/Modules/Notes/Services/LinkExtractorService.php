<?php

namespace App\Modules\Notes\Services;

use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\NoteLink;

class LinkExtractorService
{
    public function extract(Note $note): void
    {
        $note->outgoingLinks()->delete();

        $content = $note->content ?? '';
        $userId = $note->user_id;

        // Match ![[...]] (embeds) and [[...]] (wikilinks)
        preg_match_all('/(!?)\[\[([^\]]+)\]\]/', $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $isEmbed = $matches[1][$i][0] === '!';
            $inner = $matches[2][$i][0];
            $offset = $match[1];

            // Strip alias: [[target|display]] → target
            $target = explode('|', $inner)[0];
            // Strip heading/block ref: [[target#heading]] → target
            $target = explode('#', $target)[0];
            $target = trim($target);

            if (empty($target)) continue;

            // Skip cross-module prefixes (task:, event:, tx:)
            if (preg_match('/^(task|event|tx):\d+$/', $target)) continue;

            // Extract context (~50 chars around the link)
            $contextStart = max(0, $offset - 25);
            $contextLen = min(strlen($content) - $contextStart, 80);
            $context = substr($content, $contextStart, $contextLen);

            // Resolve target note by title or slug
            $targetNote = Note::where('user_id', $userId)
                ->where(function ($q) use ($target) {
                    $q->where('title', $target)
                      ->orWhere('slug', $target);
                })
                ->first();

            NoteLink::create([
                'source_note_id' => $note->id,
                'target_note_id' => $targetNote?->id,
                'target_title' => $targetNote ? null : $target,
                'link_type' => $isEmbed ? 'embed' : 'wikilink',
                'context' => $context,
            ]);
        }

        // Resolve any unresolved links pointing to this note
        NoteLink::whereNull('target_note_id')
            ->where('target_title', $note->title)
            ->update([
                'target_note_id' => $note->id,
                'target_title' => null,
            ]);
    }
}
