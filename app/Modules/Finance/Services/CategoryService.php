<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\Category;

class CategoryService
{
    public function getCategories(User $user, ?string $type = null)
    {
        $query = $user->categories()->orderBy('name');

        if ($type) {
            $query->where(function ($q) use ($type) {
                $q->where('type', $type)->orWhere('type', 'both');
            });
        }

        return $query->get();
    }

    public function createCategory(User $user, array $data): Category
    {
        return $user->categories()->create($data);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function deleteCategory(Category $category): void
    {
        $category->transactions()->update(['category_id' => null]);
        $category->delete();
    }
}
