<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::query()
            ->with(['book.owner', 'book.category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($favorites, FavoriteResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
        ]);

        $favorite = Favorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'book_id' => $data['book_id'],
        ]);

        return $this->created(new FavoriteResource($favorite->load(['book.owner', 'book.category'])));
    }

    public function destroy(Request $request, Favorite $favorite): JsonResponse
    {
        abort_unless($favorite->user_id === $request->user()->id, 403);

        $favorite->delete();

        return $this->noContent();
    }
}
