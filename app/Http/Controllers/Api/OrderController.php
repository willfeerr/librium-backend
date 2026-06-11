<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['buyer', 'seller', 'items.book'])
            ->where(function ($query) use ($request): void {
                $query->where('buyer_id', $request->user()->id)
                    ->orWhere('seller_id', $request->user()->id);
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($orders, OrderResource::class);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless(
            $request->user()->is_admin || $order->buyer_id === $request->user()->id || $order->seller_id === $request->user()->id,
            403
        );

        return $this->ok(new OrderResource($order->load(['buyer', 'seller', 'items.book'])));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'listing_id' => ['nullable', 'integer', 'exists:listings,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'from_cart' => ['sometimes', 'boolean'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.listing_id' => ['required_with:items', 'integer', 'exists:listings,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $items = $this->normalizeItems($request, $data);

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => ['Informe listing_id, items ou from_cart=true.'],
            ]);
        }

        $order = DB::transaction(function () use ($request, $items, $data): Order {
            $order = Order::query()->create([
                'buyer_id' => $request->user()->id,
                'status' => 'pending',
                'currency' => strtoupper($data['currency'] ?? 'BRL'),
                'metadata' => ['source' => ($data['from_cart'] ?? false) ? 'cart' : 'api'],
            ]);

            $sellerIds = [];
            $total = 0;

            foreach ($items as $item) {
                $listing = Listing::query()
                    ->with('book')
                    ->lockForUpdate()
                    ->findOrFail($item['listing_id']);

                if ($listing->status !== 'active' || $listing->quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["O anuncio {$listing->id} nao possui estoque suficiente."],
                    ]);
                }

                abort_if($listing->seller_id === $request->user()->id, 422, 'Voce nao pode comprar seu proprio anuncio.');

                $subtotal = (float) $listing->price * (int) $item['quantity'];
                $total += $subtotal;
                $sellerIds[] = $listing->seller_id;

                $order->items()->create([
                    'listing_id' => $listing->id,
                    'book_id' => $listing->book_id,
                    'seller_id' => $listing->seller_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $listing->price,
                    'subtotal' => $subtotal,
                ]);

                $listing->decrement('quantity', $item['quantity']);
                if ($listing->refresh()->quantity === 0) {
                    $listing->update(['status' => 'sold']);
                }
            }

            $uniqueSellerIds = array_values(array_unique($sellerIds));
            $order->update([
                'seller_id' => count($uniqueSellerIds) === 1 ? $uniqueSellerIds[0] : null,
                'total' => $total,
            ]);

            if ($data['from_cart'] ?? false) {
                CartItem::query()->where('user_id', $request->user()->id)->delete();
            }

            return $order->refresh()->load(['buyer', 'seller', 'items.book']);
        });

        return $this->created(new OrderResource($order));
    }

    private function normalizeItems(Request $request, array $data): array
    {
        if (! empty($data['items'])) {
            return $data['items'];
        }

        if (! empty($data['listing_id'])) {
            return [[
                'listing_id' => $data['listing_id'],
                'quantity' => $data['quantity'] ?? 1,
            ]];
        }

        if ($data['from_cart'] ?? false) {
            return CartItem::query()
                ->where('user_id', $request->user()->id)
                ->get(['listing_id', 'quantity'])
                ->map(fn (CartItem $item): array => [
                    'listing_id' => $item->listing_id,
                    'quantity' => $item->quantity,
                ])
                ->all();
        }

        return [];
    }
}
