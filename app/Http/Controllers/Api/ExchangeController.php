<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExchangeResource;
use App\Models\Book;
use App\Models\Exchange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $exchanges = Exchange::query()
            ->with(['requester', 'recipient', 'offeredBook', 'requestedBook'])
            ->where(function ($query) use ($request): void {
                $query->where('requester_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($exchanges, ExchangeResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'offered_book_id' => ['required', 'integer', 'exists:books,id'],
            'requested_book_id' => ['required', 'integer', 'exists:books,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $offered = Book::query()->findOrFail($data['offered_book_id']);
        $requested = Book::query()->findOrFail($data['requested_book_id']);

        abort_unless($offered->owner_id === $request->user()->id, 403);
        abort_if($requested->owner_id === $request->user()->id, 422, 'O livro solicitado ja pertence a voce.');

        $exchange = Exchange::query()->create([
            'requester_id' => $request->user()->id,
            'recipient_id' => $requested->owner_id,
            'offered_book_id' => $offered->id,
            'requested_book_id' => $requested->id,
            'message' => $data['message'] ?? null,
            'status' => 'pending',
        ]);

        return $this->created(new ExchangeResource($exchange->load(['requester', 'recipient', 'offeredBook', 'requestedBook'])));
    }

    public function accept(Request $request, Exchange $exchange): JsonResponse
    {
        abort_unless($exchange->recipient_id === $request->user()->id, 403);
        abort_unless($exchange->status === 'pending', 422, 'A troca nao esta pendente.');

        $exchange->update(['status' => 'accepted']);

        return $this->ok(new ExchangeResource($exchange->refresh()->load(['requester', 'recipient', 'offeredBook', 'requestedBook'])));
    }

    public function reject(Request $request, Exchange $exchange): JsonResponse
    {
        abort_unless($exchange->recipient_id === $request->user()->id, 403);
        abort_unless($exchange->status === 'pending', 422, 'A troca nao esta pendente.');

        $exchange->update(['status' => 'rejected']);

        return $this->ok(new ExchangeResource($exchange->refresh()->load(['requester', 'recipient', 'offeredBook', 'requestedBook'])));
    }
}
