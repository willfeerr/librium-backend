<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeConnectController extends Controller
{
    use RespondsWithApi;

    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function connect(Request $request): JsonResponse
    {
        return $this->ok($this->payments->createConnectedAccount($request->user()));
    }

    public function account(Request $request): JsonResponse
    {
        return $this->ok([
            'account_id' => $request->user()->stripe_account_id,
            'ready' => (bool) $request->user()->stripe_account_ready,
        ]);
    }
}
