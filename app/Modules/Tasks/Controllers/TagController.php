<?php

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Modules\Tasks\Models\Board;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Resources\TagResource;
use App\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function __construct(private TagService $service) {}

    public function index(Request $request)
    {
        $tags = $request->user()->tags()
            ->withCount(['boards', 'tasks'])
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'description' => 'nullable|string|max:500',
        ]);

        $slug = Str::slug($data['name']);
        abort_if($slug === '', 422, 'El nombre no puede ser solo símbolos.');

        $existing = $request->user()->tags()->where('slug', $slug)->first();
        if ($existing) {
            return new TagResource($existing);
        }

        $tag = $request->user()->tags()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'] ?? '#94a3b8',
            'description' => $data['description'] ?? null,
        ]);

        return new TagResource($tag);
    }

    public function update(Request $request, Tag $tag)
    {
        abort_unless($tag->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'sometimes|nullable|string|max:9|regex:/^#[0-9a-fA-F]{3,8}$/',
            'description' => 'sometimes|nullable|string|max:500',
        ]);

        if (array_key_exists('name', $data)) {
            $slug = Str::slug($data['name']);
            abort_if($slug === '', 422, 'Nombre inválido.');
            $clash = $request->user()->tags()
                ->where('slug', $slug)
                ->where('id', '!=', $tag->id)
                ->exists();
            abort_if($clash, 409, "Ya tienes otra etiqueta con el slug '{$slug}'.");
            $data['slug'] = $slug;
        }

        $tag->update($data);

        return new TagResource($tag);
    }

    public function destroy(Request $request, Tag $tag)
    {
        abort_unless($tag->user_id === $request->user()->id, 403);

        $tag->delete();

        return response()->json(['message' => 'Etiqueta eliminada']);
    }

    /**
     * Attach/sync tags onto a board or task.
     * POST /api/tags/attach
     */
    public function attach(Request $request)
    {
        $data = $request->validate([
            'target_type' => 'required|in:board,task',
            'target_id' => 'required|integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:100',
            'replace' => 'nullable|boolean',
        ]);

        $target = $this->resolveTarget($data['target_type'], $data['target_id'], $request->user()->id);
        abort_if($target === null, 404, ucfirst($data['target_type']) . ' no encontrado.');

        $ids = $this->service->resolveTagIds(
            $request->user(),
            $data['tag_ids'] ?? [],
            $data['tag_names'] ?? [],
        );

        $this->service->applyToTaggable($target, $ids, (bool) ($data['replace'] ?? false));

        return TagResource::collection($target->fresh()->tags);
    }

    /**
     * Detach tags from a board or task.
     * POST /api/tags/detach
     */
    public function detach(Request $request)
    {
        $data = $request->validate([
            'target_type' => 'required|in:board,task',
            'target_id' => 'required|integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'all' => 'nullable|boolean',
        ]);

        $target = $this->resolveTarget($data['target_type'], $data['target_id'], $request->user()->id);
        abort_if($target === null, 404, ucfirst($data['target_type']) . ' no encontrado.');

        if (! empty($data['all'])) {
            $target->tags()->detach();
        } else {
            abort_if(empty($data['tag_ids']), 422, 'Indica tag_ids o all=true.');
            $target->tags()->detach($data['tag_ids']);
        }

        return TagResource::collection($target->fresh()->tags);
    }

    private function resolveTarget(string $type, int $id, int $userId)
    {
        if ($type === 'board') {
            return Board::where('id', $id)->where('user_id', $userId)->first();
        }

        return Task::where('id', $id)->where('user_id', $userId)->first();
    }
}
