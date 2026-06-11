<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Book;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'book_id' => ['nullable', 'integer', 'exists:books,id'],
            'status' => ['nullable', Rule::in(Listing::STATUSES)],
        ]);

        $listings = Listing::query()
            ->with(['seller', 'book.category'])
            ->when($data['seller_id'] ?? null, fn ($query, $sellerId) => $query->where('seller_id', $sellerId))
            ->when($data['book_id'] ?? null, fn ($query, $bookId) => $query->where('book_id', $bookId))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($listings, ListingResource::class);
    }

    public function show(Listing $listing): JsonResponse
    {
        return $this->ok(new ListingResource($listing->load(['seller', 'book.category'])));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(Listing::STATUSES)],
        ]);

        $book = Book::query()->findOrFail($data['book_id']);
        abort_unless($request->user()->is_admin || $book->owner_id === $request->user()->id, 403);

        $data['seller_id'] = $request->user()->id;
        $data['status'] ??= 'active';

        return $this->created(new ListingResource(Listing::query()->create($data)->load(['seller', 'book.category'])));
    }

    public function update(Request $request, Listing $listing): JsonResponse
    {
        abort_unless($request->user()->is_admin || $listing->seller_id === $request->user()->id, 403);

        $data = $request->validate([
            'price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(Listing::STATUSES)],
        ]);

        $listing->update($data);

        return $this->ok(new ListingResource($listing->refresh()->load(['seller', 'book.category'])));
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        abort_unless($request->user()->is_admin || $listing->seller_id === $request->user()->id, 403);

        $listing->delete();

        return $this->noContent();
    }
}
