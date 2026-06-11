<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated($categories, CategoryResource::class);
    }

    public function show(Category $category): JsonResponse
    {
        return $this->ok(new CategoryResource($category));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']));

        return $this->created(new CategoryResource(Category::query()->create($data)));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:140', 'unique:categories,slug,'.$category->id],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug(Str::slug($data['name']), $category->id);
        }

        $category->update($data);

        return $this->ok(new CategoryResource($category->refresh()));
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $category->delete();

        return $this->noContent();
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $counter = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
