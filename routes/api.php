<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentLinkController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Sandbox\YapilyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// -------------------------------------------------------------------------
// Sandbox: Yapily (dummy bank / open-banking). Feature-flagged.
// When ENABLE_YAPILY_SANDBOX=false, endpoints return 403 Sandbox Disabled.
// -------------------------------------------------------------------------
Route::prefix('sandbox/yapily')->group(function () {
    Route::get('/institutions', [YapilyController::class, 'institutions'])
        ->name('api.sandbox.yapily.institutions');
});

// Public webhook receiver (no auth required)
Route::post('/webhooks/receive', [WebhookController::class, 'receive'])
    ->name('api.webhooks.receive');

// Unified acquirer callback/webhook endpoint (no auth required)
// This handles callbacks from all acquirer providers (Razorpay, Paytm, etc.)
Route::post('/webhooks/acquirer', [\App\Http\Controllers\Api\AcquirerCallbackController::class, 'handle'])
    ->name('api.webhooks.acquirer');

// Quick access routes (without v1 prefix) - for test app
Route::middleware([\App\Http\Middleware\AuthenticateApiKey::class])->group(function () {
    Route::post('/payments', [PaymentController::class, 'createPayment'])->name('api.payments.create');
    Route::get('/payments/{transactionId}', [TransactionController::class, 'show'])->name('api.payments.show');
    Route::get('/webhooks/logs/{transactionId}', [WebhookController::class, 'getLogs'])->name('api.webhooks.logs');
});

// API v1 routes with API key authentication
Route::prefix('v1')->middleware(['App\Http\Middleware\AuthenticateApiKey'])->group(function () {
    
    // Payment endpoints
    Route::post('/payment', [PaymentController::class, 'createPayment'])
        ->name('api.payment.create');
    
    Route::get('/payment/{transactionId}/verify', [PaymentController::class, 'verifyPayment'])
        ->name('api.payment.verify');

    // Order endpoints
    Route::get('/orders', [OrderController::class, 'index'])
        ->name('api.orders.index');
    
    Route::get('/orders/{orderId}', [OrderController::class, 'show'])
        ->name('api.orders.show');

    // Transaction endpoints
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->name('api.transactions.index');
    
    Route::get('/transactions/{transactionId}', [TransactionController::class, 'show'])
        ->name('api.transactions.show');

    // Refund endpoints
    Route::post('/refunds', [RefundController::class, 'create'])
        ->name('api.refunds.create');
    
    Route::get('/refunds', [RefundController::class, 'index'])
        ->name('api.refunds.index');

    // Payment Link endpoints
    Route::post('/payment_links', [PaymentLinkController::class, 'create'])
        ->name('api.payment_links.create');
    
    Route::get('/payment_links', [PaymentLinkController::class, 'index'])
        ->name('api.payment_links.index');

    // Settlement endpoints (LIVE mode only)
    Route::get('/settlements', [SettlementController::class, 'index'])
        ->name('api.settlements.index');

    Route::get('/settlements/{settlementId}', [SettlementController::class, 'show'])
        ->name('api.settlements.show');

    // Unified status check endpoints
    Route::get('/status', [StatusController::class, 'index'])
        ->name('api.status.index');

    Route::get('/status/transaction/{transactionId}', [StatusController::class, 'transaction'])
        ->name('api.status.transaction');

    Route::get('/status/order/{orderId}', [StatusController::class, 'order'])
        ->name('api.status.order');

    Route::get('/status/refund/{refundId}', [StatusController::class, 'refund'])
        ->name('api.status.refund');

    Route::get('/status/payment-link/{token}', [StatusController::class, 'paymentLink'])
        ->name('api.status.payment_link');

    // Webhook test endpoint
    Route::post('/webhooks/test', [WebhookController::class, 'test'])
        ->name('api.webhooks.test');
});

