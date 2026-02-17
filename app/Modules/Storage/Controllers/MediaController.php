<?php

namespace App\Modules\Storage\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Storage\StorageService;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    public function __construct(private StorageService $service) {}

    public function store(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $limit = $request->user()->getModuleLimit('storage', 'max_files');
        if ($limit !== null) {
            $current = $this->service->getMediaCount($request->user());
            abort_if($current >= $limit, 422, "Has alcanzado el límite de {$limit} archivos.");
        }

        $media = $transaction
            ->addMediaFromRequest('file')
            ->toMediaCollection('transaction-attachments', 'public');

        return response()->json(['data' => $media], 201);
    }

    public function destroy(Request $request, Transaction $transaction, Media $media)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        abort_unless((int) $media->model_id === (int) $transaction->id, 404);

        $this->service->deleteMedia($media);

        return response()->json(['message' => 'Archivo eliminado']);
    }

    public function download(Request $request, Transaction $transaction, Media $media)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        abort_unless((int) $media->model_id === (int) $transaction->id, 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
