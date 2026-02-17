<?php

namespace App\Modules\Storage;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StorageService
{
    public function getAllMedia(User $user)
    {
        return Media::where('model_type', \App\Modules\Finance\Models\Transaction::class)
            ->whereHasMorph('model', [\App\Modules\Finance\Models\Transaction::class], function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getMediaCount(User $user): int
    {
        return Media::where('model_type', \App\Modules\Finance\Models\Transaction::class)
            ->whereHasMorph('model', [\App\Modules\Finance\Models\Transaction::class], function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();
    }

    public function deleteMedia(Media $media): void
    {
        $media->delete();
    }
}
