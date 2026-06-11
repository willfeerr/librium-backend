<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReview;
use App\Models\Exchange;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use RespondsWithApi;

    public function dashboard(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);

        return $this->ok([
            'users' => User::query()->count(),
            'books' => Book::query()->count(),
            'sales' => Order::query()->whereIn('status', ['paid', 'shipped', 'completed'])->count(),
            'exchanges' => Exchange::query()->count(),
            'revenue' => (float) Order::query()->whereIn('status', ['paid', 'shipped', 'completed'])->sum('total'),
            'reviews' => BookReview::query()->count(),
        ]);
    }
}
