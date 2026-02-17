<?php

namespace App\Modules\Storage\Controllers;

use App\Http\Controllers\Controller;
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
