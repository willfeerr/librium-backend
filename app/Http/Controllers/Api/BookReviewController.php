<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookReviewResource;
use App\Models\Book;
use App\Models\BookReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookReviewController extends Controller
{
    use RespondsWithApi;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $review = BookReview::query()->updateOrCreate(
            ['book_id' => $data['book_id'], 'user_id' => $request->user()->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        $review->book->refreshRating();

        return $this->created(new BookReviewResource($review->load('user')));
    }

    public function byBook(Request $request, Book $book): JsonResponse
    {
        $reviews = $book->reviews()
            ->with('user')
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($reviews, BookReviewResource::class);
    }

    public function destroy(Request $request, BookReview $bookReview): JsonResponse
    {
        abort_unless($request->user()->is_admin || $request->user()->id === $bookReview->user_id, 403);

        $book = $bookReview->book;
        $bookReview->delete();
        $book->refreshRating();

        return $this->noContent();
    }
}
