<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentService
{
    public function createCheckout(Order $order, array $options = []): array
    {
        $order->loadMissing(['items.book', 'seller']);
        $stripe = $this->stripe();

        if (! $stripe) {
            return [
                'mode' => 'mock',
                'order_id' => $order->id,
                'checkout_url' => url('/checkout/mock/'.$order->id),
                'message' => 'Configure STRIPE_SECRET para criar sessoes reais de checkout.',
            ];
        }

        $paymentIntentData = [];
        if ($order->seller?->stripe_account_id) {
            $paymentIntentData = [
                'application_fee_amount' => $this->platformFeeAmount($order),
                'transfer_data' => ['destination' => $order->seller->stripe_account_id],
            ];
        }

        $session = $stripe->checkout->sessions->create(array_filter([
            'mode' => 'payment',
            'success_url' => $options['success_url'] ?? url('/payments/success?order_id='.$order->id),
            'cancel_url' => $options['cancel_url'] ?? url('/payments/cancel?order_id='.$order->id),
            'line_items' => $order->items->map(fn ($item): array => [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => (int) round(((float) $item->unit_price) * 100),
                    'product_data' => [
                        'name' => $item->book?->title ?? 'Livro',
                    ],
                ],
            ])->values()->all(),
            'payment_method_types' => ['card', 'pix'],
            'metadata' => ['order_id' => (string) $order->id],
            'payment_intent_data' => $paymentIntentData,
        ]));

        $order->update(['payment_reference' => $session->id]);

        return [
            'mode' => 'stripe',
            'order_id' => $order->id,
            'checkout_session_id' => $session->id,
            'checkout_url' => $session->url,
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $payload = $request->getContent();
        $event = json_decode($payload, true) ?: [];

        if ($stripeSecret = config('services.stripe.webhook_secret')) {
            $signature = $request->header('Stripe-Signature');
            if ($signature && class_exists(\Stripe\Webhook::class)) {
                $eventObject = \Stripe\Webhook::constructEvent($payload, $signature, $stripeSecret);
                $event = $eventObject->toArray();
            }
        }

        if (($event['type'] ?? null) === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $orderId = $session['metadata']['order_id'] ?? null;

            if ($orderId) {
                Order::query()->whereKey($orderId)->update([
                    'status' => 'paid',
                    'payment_reference' => $session['id'] ?? null,
                ]);
            }
        }

        return ['received' => true, 'type' => $event['type'] ?? null];
    }

    public function refund(Order $order, ?string $reason = null): array
    {
        $stripe = $this->stripe();

        if (! $stripe || ! $order->payment_reference) {
            $order->update(['status' => 'refunded']);

            return [
                'mode' => 'mock',
                'order_id' => $order->id,
                'status' => 'refunded',
                'reason' => $reason,
            ];
        }

        $paymentIntent = $order->payment_reference;
        if (str_starts_with((string) $paymentIntent, 'cs_')) {
            $session = $stripe->checkout->sessions->retrieve($order->payment_reference);
            $paymentIntent = $session->payment_intent;
        }

        $payload = ['payment_intent' => $paymentIntent];
        if (in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)) {
            $payload['reason'] = $reason;
        }

        $refund = $stripe->refunds->create($payload);

        $order->update(['status' => 'refunded']);

        return [
            'mode' => 'stripe',
            'order_id' => $order->id,
            'refund_id' => $refund->id,
            'status' => $refund->status,
        ];
    }

    public function createConnectedAccount(User $user): array
    {
        $stripe = $this->stripe();

        if (! $stripe) {
            $user->update([
                'stripe_account_id' => $user->stripe_account_id ?: 'acct_mock_'.$user->id,
                'stripe_account_ready' => false,
            ]);

            return [
                'mode' => 'mock',
                'account_id' => $user->stripe_account_id,
                'onboarding_url' => url('/stripe/connect/mock/'.$user->id),
            ];
        }

        $accountId = $user->stripe_account_id;
        if (! $accountId) {
            $account = $stripe->accounts->create([
                'type' => 'express',
                'country' => 'BR',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);
            $accountId = $account->id;
            $user->update(['stripe_account_id' => $accountId]);
        }

        $link = $stripe->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => url('/stripe/connect/refresh'),
            'return_url' => url('/stripe/connect/return'),
            'type' => 'account_onboarding',
        ]);

        return [
            'mode' => 'stripe',
            'account_id' => $accountId,
            'onboarding_url' => $link->url,
        ];
    }

    private function platformFeeAmount(Order $order): int
    {
        $percent = (float) config('services.stripe.platform_fee_percent', 8);

        return (int) round(((float) $order->total) * ($percent / 100) * 100);
    }

    private function stripe(): ?object
    {
        $secret = config('services.stripe.secret');

        if (! $secret || ! class_exists(\Stripe\StripeClient::class)) {
            return null;
        }

        return new \Stripe\StripeClient($secret);
    }
}
