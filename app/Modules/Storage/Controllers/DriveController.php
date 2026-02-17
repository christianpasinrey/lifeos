<?php

namespace App\Modules\Storage\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Storage\Agents\FileAnalyzer;
use App\Modules\Storage\StorageService;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DriveController extends Controller
{
    public function __construct(private StorageService $service) {}

    public function index(Request $request)
    {
        $files = $this->service->getAllMedia(
            $request->user(),
            $request->get('search'),
            $request->get('category'),
        );

        return response()->json(['data' => $files->toArray()]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'file_count' => $this->service->getTotalFileCount($user),
                'total_bytes' => $this->service->getTotalStorageBytes($user),
                'limit_files' => $user->getModuleLimit('storage', 'max_files'),
                'limit_file_size_kb' => $user->getModuleLimit('storage', 'max_file_size_kb'),
                'limit_storage_mb' => $user->getModuleLimit('storage', 'max_storage_mb'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $maxSizeKb = $user->getModuleLimit('storage', 'max_file_size_kb') ?? 10240;

        $request->validate([
            'file' => "required|file|max:{$maxSizeKb}",
        ]);

        // Check file count limit
        $fileLimit = $user->getModuleLimit('storage', 'max_files');
        if ($fileLimit !== null) {
            $current = $this->service->getTotalFileCount($user);
            abort_if($current >= $fileLimit, 422, "Has alcanzado el límite de {$fileLimit} archivos.");
        }

        // Check storage limit
        $storageLimitMb = $user->getModuleLimit('storage', 'max_storage_mb');
        if ($storageLimitMb !== null) {
            $currentBytes = $this->service->getTotalStorageBytes($user);
            $fileBytes = $request->file('file')->getSize();
            $limitBytes = $storageLimitMb * 1024 * 1024;
            abort_if(($currentBytes + $fileBytes) > $limitBytes, 422, "Has alcanzado el límite de {$storageLimitMb} MB de almacenamiento.");
        }

        $media = $user
            ->addMediaFromRequest('file')
            ->toMediaCollection('drive', 'public');

        return response()->json(['data' => $media], 201);
    }

    public function destroy(Request $request, Media $media)
    {
        $this->authorizeMedia($request, $media);

        $this->service->deleteMedia($media);

        return response()->json(['message' => 'Archivo eliminado']);
    }

    public function download(Request $request, Media $media)
    {
        $this->authorizeMedia($request, $media);

        return response()->download($media->getPath(), $media->file_name);
    }

    public function preview(Request $request, Media $media)
    {
        $this->authorizeMedia($request, $media);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    public function analyze(Request $request, Media $media)
    {
        $this->authorizeMedia($request, $media);

        $request->validate([
            'prompt' => 'required|string|max:2000',
            'content' => 'nullable|string|max:200000',
        ]);

        // Max 500KB for analysis
        abort_if($media->size > 512000, 422, 'El archivo es demasiado grande para analizar (máx 500 KB).');

        $content = $request->input('content');

        // If no content provided by frontend, try to extract text server-side
        if (!$content) {
            $content = $this->extractTextContent($media);
        }

        abort_if(!$content, 422, 'No se pudo extraer el contenido del archivo para analizarlo.');

        // Truncate if too long for the LLM context
        if (mb_strlen($content) > 50000) {
            $content = mb_substr($content, 0, 50000) . "\n\n[... contenido truncado por longitud ...]";
        }

        $agent = new FileAnalyzer($request->user(), $media->file_name, $content);
        $response = $agent->prompt($request->input('prompt'));

        return response()->json([
            'text' => $response->text,
        ]);
    }

    private function extractTextContent(Media $media): ?string
    {
        $path = $media->getPath();
        $mime = $media->mime_type;

        // Text-based files
        if (str_starts_with($mime, 'text/') || $mime === 'application/json' || $mime === 'application/xml') {
            return @file_get_contents($path);
        }

        // PDF
        if ($mime === 'application/pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);

                return $pdf->getText();
            } catch (\Throwable) {
                return null;
            }
        }

        // CSV
        if ($mime === 'text/csv' || str_ends_with($media->file_name, '.csv')) {
            return @file_get_contents($path);
        }

        // Excel (.xlsx, .xls)
        if (in_array($mime, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ])) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $lines = [];

                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $lines[] = "--- Hoja: {$sheet->getTitle()} ---";
                    foreach ($sheet->toArray() as $row) {
                        $lines[] = implode("\t", array_map(fn ($c) => (string) ($c ?? ''), $row));
                    }
                }

                return implode("\n", $lines);
            } catch (\Throwable) {
                return null;
            }
        }

        // Word (.docx)
        if ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            try {
                $zip = new \ZipArchive();
                if ($zip->open($path) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($xml) {
                        $text = strip_tags(str_replace('<', ' <', $xml));

                        return preg_replace('/\s+/', ' ', trim($text));
                    }
                }
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function authorizeMedia(Request $request, Media $media): void
    {
        $userId = $request->user()->id;

        // Drive file (owned directly by user)
        if ($media->model_type === \App\Models\User::class) {
            abort_unless((int) $media->model_id === $userId, 403);
            return;
        }

        // Transaction attachment
        abort_unless(
            $media->model && $media->model->user_id === $userId,
            403
        );
    }
}
