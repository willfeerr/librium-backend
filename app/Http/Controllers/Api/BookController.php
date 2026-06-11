<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'condition' => ['nullable', Rule::in(Book::CONDITIONS)],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
        ]);

        $books = Book::query()
            ->with(['owner', 'category'])
            ->filtered($filters)
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($books, BookResource::class);
    }

    public function show(Book $book): JsonResponse
    {
        return $this->ok(new BookResource($book->load(['owner', 'category'])));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedBook($request);
        $data['owner_id'] = $request->user()->id;

        return $this->created(new BookResource(Book::query()->create($data)->load(['owner', 'category'])));
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        abort_unless($request->user()->is_admin || $request->user()->id === $book->owner_id, 403);

        $book->update($this->validatedBook($request, true));

        return $this->ok(new BookResource($book->refresh()->load(['owner', 'category'])));
    }

    public function destroy(Request $request, Book $book): JsonResponse
    {
        abort_unless($request->user()->is_admin || $request->user()->id === $book->owner_id, 403);

        $book->delete();

        return $this->noContent();
    }

    private function validatedBook(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'title' => [$required, 'string', 'max:255'],
            'author' => [$required, 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:10000'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date'],
            'condition' => [$required, Rule::in(Book::CONDITIONS)],
            'price' => [$required, 'numeric', 'min:0'],
            'cover' => ['nullable', 'string', 'max:2048'],
            'status' => ['sometimes', Rule::in(['available', 'unavailable', 'reserved'])],
        ]);
    }
}
