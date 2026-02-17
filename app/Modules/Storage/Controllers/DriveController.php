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
        $files = $this->service->getAllMedia($request->user());

        return response()->json(['data' => $files]);
    }

    public function destroy(Request $request, Media $media)
    {
        abort_unless(
            $media->model && $media->model->user_id === $request->user()->id,
            403
        );

        $this->service->deleteMedia($media);

        return response()->json(['message' => 'Archivo eliminado']);
    }

    public function download(Request $request, Media $media)
    {
        abort_unless(
            $media->model && $media->model->user_id === $request->user()->id,
            403
        );

        return response()->download($media->getPath(), $media->file_name);
    }
}
