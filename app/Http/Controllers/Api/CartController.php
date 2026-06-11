<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $items = CartItem::query()
            ->with(['listing.book.category', 'listing.seller'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->ok([
            'items' => CartItemResource::collection($items),
            'total' => $items->sum(fn (CartItem $item): float => (float) $item->listing->price * $item->quantity),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $listing = Listing::query()->findOrFail($data['listing_id']);
        abort_if($listing->seller_id === $request->user()->id, 422, 'Voce nao pode adicionar seu proprio anuncio ao carrinho.');
        abort_if($listing->status !== 'active' || $listing->quantity < $data['quantity'], 422, 'Anuncio indisponivel ou sem estoque suficiente.');

        $item = CartItem::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'listing_id' => $listing->id],
            ['quantity' => $data['quantity']]
        );

        return $this->created(new CartItemResource($item->load(['listing.book.category', 'listing.seller'])));
    }

    public function destroy(Request $request, CartItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $item->delete();

        return $this->noContent();
    }
}
