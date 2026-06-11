<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\SellerReviewResource;
use App\Models\Order;
use App\Models\SellerReview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerReviewController extends Controller
{
    use RespondsWithApi;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller_id' => ['required', 'integer', 'exists:users,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        abort_if((int) $data['seller_id'] === $request->user()->id, 422, 'Voce nao pode avaliar a si mesmo.');

        if (! empty($data['order_id'])) {
            $order = Order::query()->findOrFail($data['order_id']);
            abort_unless($order->buyer_id === $request->user()->id && $order->seller_id === (int) $data['seller_id'], 403);
        }

        $review = SellerReview::query()->updateOrCreate(
            [
                'seller_id' => $data['seller_id'],
                'reviewer_id' => $request->user()->id,
                'order_id' => $data['order_id'] ?? null,
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        $this->refreshSellerRating($review->seller);

        return $this->created(new SellerReviewResource($review->load('reviewer')));
    }

    public function byUser(Request $request, User $user): JsonResponse
    {
        $reviews = $user->sellerReviews()
            ->with('reviewer')
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($reviews, SellerReviewResource::class);
    }

    private function refreshSellerRating(User $seller): void
    {
        $seller->forceFill([
            'rating' => round($seller->sellerReviews()->avg('rating') ?? 0, 2),
        ])->save();
    }
}
