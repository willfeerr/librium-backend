<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookReviewController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DocsController;
use App\Http\Controllers\Api\ExchangeController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SellerReviewController;
use App\Http\Controllers\Api\StripeConnectController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => config('app.name'),
    'environment' => app()->environment(),
    'timestamp' => now()->toISOString(),
]));

Route::get('/docs', [DocsController::class, 'index']);
Route::get('/docs/openapi.json', [DocsController::class, 'openapi']);

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/reset-password/{token}', fn (string $token) => response()->json([
        'token' => $token,
        'email' => request('email'),
    ]))->name('password.reset');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
});

Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

Route::apiResource('books', BookController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('listings', ListingController::class)->only(['index', 'show']);
Route::get('/books/{book}/reviews', [BookReviewController::class, 'byBook']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/email/verification-notification', [AuthController::class, 'sendVerification']);
    });

    Route::apiResource('users', UserController::class);

    Route::apiResource('books', BookController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);

    Route::post('/book-reviews', [BookReviewController::class, 'store']);
    Route::delete('/book-reviews/{bookReview}', [BookReviewController::class, 'destroy']);
    Route::post('/seller-reviews', [SellerReviewController::class, 'store']);
    Route::get('/users/{user}/reviews', [SellerReviewController::class, 'byUser']);

    Route::apiResource('listings', ListingController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);

    Route::get('/exchanges', [ExchangeController::class, 'index']);
    Route::post('/exchanges', [ExchangeController::class, 'store']);
    Route::put('/exchanges/{exchange}/accept', [ExchangeController::class, 'accept']);
    Route::put('/exchanges/{exchange}/reject', [ExchangeController::class, 'reject']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage']);
    Route::put('/conversations/{conversation}/messages/{message}/read', [ConversationController::class, 'read']);
    Route::post('/conversations/{conversation}/typing', [ConversationController::class, 'typing']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy']);

    Route::post('/payments/checkout', [PaymentController::class, 'checkout']);
    Route::post('/payments/refund', [PaymentController::class, 'refund']);
    Route::post('/stripe/connect', [StripeConnectController::class, 'connect']);
    Route::get('/stripe/account', [StripeConnectController::class, 'account']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'read']);

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::post('/uploads', [UploadController::class, 'store']);
});
