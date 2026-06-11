<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    use RespondsWithApi;

    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'success_url' => ['nullable', 'url'],
            'cancel_url' => ['nullable', 'url'],
        ]);

        $order = Order::query()->findOrFail($data['order_id']);
        abort_unless($request->user()->is_admin || $order->buyer_id === $request->user()->id, 403);

        return $this->ok($this->payments->createCheckout($order, $data));
    }

    public function webhook(Request $request): JsonResponse
    {
        return $this->ok($this->payments->handleWebhook($request));
    }

    public function refund(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'reason' => ['nullable', 'string', Rule::in(['duplicate', 'fraudulent', 'requested_by_customer'])],
        ]);

        $order = Order::query()->findOrFail($data['order_id']);
        abort_unless($request->user()->is_admin || $order->buyer_id === $request->user()->id, 403);

        return $this->ok($this->payments->refund($order, $data['reason'] ?? null));
    }
}
