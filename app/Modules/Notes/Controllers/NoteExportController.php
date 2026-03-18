<?php

namespace App\Modules\Notes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Models\Note;
use App\Modules\Notes\Models\NoteFolder;
use App\Modules\Notes\Services\ExportService;
use Illuminate\Http\Request;

class NoteExportController extends Controller
{
    public function __construct(private ExportService $exportService) {}

    public function note(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $md = $this->exportService->noteToMarkdown($note);
        $filename = ($note->slug ?: 'note') . '.md';

        return response($md, 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function folder(Request $request, NoteFolder $folder)
    {
        abort_unless($folder->user_id === $request->user()->id, 403);

        $zipPath = $this->exportService->folderToZip($folder);

        return response()->download($zipPath, $folder->slug . '.zip')
            ->deleteFileAfterSend();
    }

    public function vault(Request $request)
    {
        $zipPath = $this->exportService->vaultToZip($request->user());

        return response()->download($zipPath, 'vault.zip')
            ->deleteFileAfterSend();
    }
}
