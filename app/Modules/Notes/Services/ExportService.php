<?php

namespace App\Modules\Notes\Services;

use App\Models\User;
use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\NoteFolder;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;

class ExportService
{
    public function noteToMarkdown(Note $note): string
    {
        $frontmatter = $this->buildFrontmatter($note);
        $content = $note->content ?? '';

        if (!empty($frontmatter)) {
            return "---\n{$frontmatter}---\n\n{$content}";
        }

        return $content;
    }

    public function folderToZip(NoteFolder $folder): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'notes_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        $this->addFolderToZip($zip, $folder, '');

        $zip->close();

        return $zipPath;
    }

    public function vaultToZip(User $user): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'vault_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // Root-level notes
        $rootNotes = $user->notes()->whereNull('folder_id')->get();
        foreach ($rootNotes as $note) {
            $zip->addFromString($note->slug . '.md', $this->noteToMarkdown($note));
        }

        // Folders
        $rootFolders = $user->noteFolders()->whereNull('parent_id')->get();
        foreach ($rootFolders as $folder) {
            $this->addFolderToZip($zip, $folder, '');
        }

        $zip->close();

        return $zipPath;
    }

    private function addFolderToZip(ZipArchive $zip, NoteFolder $folder, string $prefix): void
    {
        $path = $prefix ? "{$prefix}/{$folder->name}" : $folder->name;
        $zip->addEmptyDir($path);

        foreach ($folder->notes as $note) {
            $zip->addFromString("{$path}/{$note->slug}.md", $this->noteToMarkdown($note));
        }

        foreach ($folder->children as $child) {
            $this->addFolderToZip($zip, $child, $path);
        }
    }

    private function buildFrontmatter(Note $note): string
    {
        $props = $note->properties ?? [];

        // Add tags from relation
        $tags = $note->tags->pluck('full_path')->toArray();
        if (!empty($tags)) {
            $props['tags'] = $tags;
        }

        if (empty($props)) {
            return '';
        }

        return Yaml::dump($props, 2, 2);
    }
}
