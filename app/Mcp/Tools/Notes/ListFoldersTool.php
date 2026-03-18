<?php

namespace App\Mcp\Tools\Notes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListFoldersTool extends Tool
{
    protected string $description = 'Lists all note folders for the user, with note counts.';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (!$user->hasModule('notes')) {
            return Response::text('Error: Notes module is not active.');
        }

        $folders = $user->noteFolders()
            ->withCount('allNotes')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        if ($folders->isEmpty()) {
            return Response::text('No folders found.');
        }

        $output = "Note folders:\n\n";
        foreach ($folders as $folder) {
            $output .= $this->renderFolder($folder, 0);
        }

        return Response::text($output);
    }

    private function renderFolder($folder, int $depth): string
    {
        $indent = str_repeat('  ', $depth);
        $output = "{$indent}\xF0\x9F\x93\x81 {$folder->name} (ID: {$folder->id}) — {$folder->all_notes_count} notes\n";

        foreach ($folder->children ?? [] as $child) {
            $output .= $this->renderFolder($child, $depth + 1);
        }

        return $output;
    }
}
