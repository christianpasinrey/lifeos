<?php

namespace App\Modules\Storage;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StorageService
{
    /**
     * All files owned by user (drive + transaction attachments).
     */
    public function getAllMedia(User $user, ?string $search = null, ?string $category = null)
    {
        $query = $this->userMediaQuery($user);

        if ($search) {
            $query->where('file_name', 'like', '%' . $search . '%');
        }

        if ($category && $category !== 'all') {
            $mimePatterns = $this->mimePatternsForCategory($category);
            if ($mimePatterns) {
                $query->where(function ($q) use ($mimePatterns) {
                    foreach ($mimePatterns as $pattern) {
                        $q->orWhere('mime_type', 'like', $pattern);
                    }
                });
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getTotalFileCount(User $user): int
    {
        return $this->userMediaQuery($user)->count();
    }

    public function getTotalStorageBytes(User $user): int
    {
        return (int) $this->userMediaQuery($user)->sum('size');
    }

    public function deleteMedia(Media $media): void
    {
        $media->delete();
    }

    /**
     * Single query that gets all media belonging to a user
     * (both direct drive uploads and transaction attachments).
     */
    private function userMediaQuery(User $user)
    {
        $txClass = \App\Modules\Finance\Models\Transaction::class;

        return Media::where(function ($q) use ($user, $txClass) {
            // Drive files (attached to User model)
            $q->where(function ($q2) use ($user) {
                $q2->where('model_type', User::class)
                    ->where('model_id', $user->id);
            })
            // Transaction attachments
            ->orWhere(function ($q2) use ($user, $txClass) {
                $q2->where('model_type', $txClass)
                    ->whereIn('model_id', $user->transactions()->select('id'));
            });
        });
    }

    private function mimePatternsForCategory(string $category): ?array
    {
        return match ($category) {
            'images' => ['image/%'],
            'videos' => ['video/%'],
            'audio' => ['audio/%'],
            'documents' => ['application/pdf', '%word%', '%opendocument.text%'],
            'spreadsheets' => ['%sheet%', '%excel%', '%csv%'],
            'text' => ['text/%'],
            'other' => null, // handled below
            default => null,
        };
    }
}
