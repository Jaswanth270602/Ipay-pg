<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DocsController extends Controller
{
    public function index(): View
    {
        $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');

        $apis = [
            [
                'name' => 'Create Payment',
                'method' => 'POST',
                'endpoint' => '/api/v1/payment',
                'auth_required' => true,
                'description' => 'Creates a payment session and returns checkout URL.',
                'headers' => ['X-API-KEY: pk_test_xxx or pk_live_xxx', 'Content-Type: application/json'],
                'path_params' => [],
                'query_params' => [],
                'body_fields' => [
                    ['name' => 'amount', 'type' => 'number', 'required' => true, 'description' => 'Amount (min 1).'],
                    ['name' => 'currency', 'type' => 'string(3)', 'required' => false, 'description' => 'ISO currency code.'],
                    ['name' => 'description', 'type' => 'string(max:500)', 'required' => false, 'description' => 'Payment description.'],
                    ['name' => 'customer_details', 'type' => 'object', 'required' => false, 'description' => 'Customer details.'],
                    ['name' => 'return_url', 'type' => 'string(url)', 'required' => false, 'description' => 'Post-payment return URL.'],
                    ['name' => 'cancel_url', 'type' => 'string(url)', 'required' => false, 'description' => 'Cancellation return URL.'],
                    ['name' => 'metadata', 'type' => 'object', 'required' => false, 'description' => 'Custom metadata object.'],
                ],
                'request' => [
                    'amount' => 1000,
                    'currency' => 'USD',
                    'description' => 'Order ORD-10001',
                    'customer_details' => ['name' => 'John Doe', 'email' => 'john@example.com'],
                    'return_url' => 'https://merchant.example.com/return',
                    'cancel_url' => 'https://merchant.example.com/cancel',
                    'metadata' => ['order_id' => 'ORD-10001'],
                ],
                'success' => [
                    'success' => true,
                    'payment_url' => 'http://127.0.0.1:8000/pay/PL_xxxxxxxxxxxxx',
                    'checkout_url' => 'http://127.0.0.1:8000/pay/PL_xxxxxxxxxxxxx',
                    'link_token' => 'PL_xxxxxxxxxxxxx',
                    'amount' => 1000,
                    'currency' => 'USD',
                    'expires_at' => '2026-03-24T12:00:00+00:00',
                ],
                'response_fields' => [
                    ['name' => 'success', 'type' => 'boolean', 'description' => 'API call status.'],
                    ['name' => 'payment_url', 'type' => 'string(url)', 'description' => 'Hosted payment URL.'],
                    ['name' => 'link_token', 'type' => 'string', 'description' => 'Payment link token.'],
                    ['name' => 'expires_at', 'type' => 'string(datetime)', 'description' => 'Expiry timestamp.'],
                ],
                'errors' => [
                    ['status' => 422, 'body' => ['error' => 'Validation failed']],
                    ['status' => 401, 'body' => ['error' => 'Invalid or expired API key']],
                ],
            ],
            [
                'name' => 'Verify Payment',
                'method' => 'GET',
                'endpoint' => '/api/v1/payment/{transactionId}/verify',
                'auth_required' => true,
                'description' => 'Verifies payment status by transaction ID.',
                'headers' => ['X-API-KEY: pk_test_xxx or pk_live_xxx'],
                'path_params' => [['name' => 'transactionId', 'type' => 'string', 'description' => 'Transaction ID (txn_id).']],
                'query_params' => [],
                'body_fields' => [],
                'request' => null,
                'success' => [
                    'success' => true,
                    'data' => [
                        'transaction_id' => 'txn_123',
                        'status' => 'success',
                        'verified' => true,
                        'amount' => 1000,
                        'currency' => 'USD',
                    ],
                ],
                'response_fields' => [
                    ['name' => 'data.transaction_id', 'type' => 'string', 'description' => 'Transaction ID.'],
                    ['name' => 'data.status', 'type' => 'string', 'description' => 'Transaction status.'],
                    ['name' => 'data.verified', 'type' => 'boolean', 'description' => 'Verification result.'],
                ],
                'errors' => [
                    ['status' => 404, 'body' => ['error' => 'Transaction not found']],
                    ['status' => 500, 'body' => ['error' => 'Verification failed']],
                ],
            ],
            [
                'name' => 'Create Refund',
                'method' => 'POST',
                'endpoint' => '/api/v1/refunds',
                'auth_required' => true,
                'description' => 'Creates full/partial refund for successful transaction.',
                'headers' => ['X-API-KEY: pk_test_xxx or pk_live_xxx', 'Content-Type: application/json'],
                'path_params' => [],
                'query_params' => [],
                'body_fields' => [
                    ['name' => 'transaction_id', 'type' => 'string', 'required' => true, 'description' => 'Transaction ID to refund.'],
                    ['name' => 'amount', 'type' => 'number', 'required' => false, 'description' => 'Partial refund amount.'],
                    ['name' => 'reason', 'type' => 'string(max:500)', 'required' => false, 'description' => 'Refund reason.'],
                ],
                'request' => ['transaction_id' => 'txn_123', 'amount' => 250, 'reason' => 'Customer requested partial refund'],
                'success' => ['success' => true, 'data' => ['refund_id' => 'rfnd_123', 'status' => 'pending', 'amount' => 250]],
                'response_fields' => [
                    ['name' => 'data.refund_id', 'type' => 'string', 'description' => 'Refund ID.'],
                    ['name' => 'data.status', 'type' => 'string', 'description' => 'Refund status.'],
                ],
                'errors' => [
                    ['status' => 404, 'body' => ['error' => 'Transaction not found']],
                    ['status' => 400, 'body' => ['error' => 'Cannot refund unsuccessful transaction']],
                ],
            ],
            [
                'name' => 'Webhook Receiver (Public)',
                'method' => 'POST',
                'endpoint' => '/api/webhooks/receive',
                'auth_required' => false,
                'description' => 'Generic webhook receiver for provider callbacks.',
                'headers' => ['Content-Type: application/json'],
                'path_params' => [],
                'query_params' => [],
                'body_fields' => [['name' => 'event', 'type' => 'string', 'required' => false, 'description' => 'Provider event type.']],
                'request' => ['event' => 'payment.success', 'data' => ['gateway_payment_id' => 'pay_123']],
                'success' => ['success' => true, 'message' => 'Webhook received and queued for processing'],
                'response_fields' => [
                    ['name' => 'success', 'type' => 'boolean', 'description' => 'Acceptance status.'],
                    ['name' => 'message', 'type' => 'string', 'description' => 'Processing message.'],
                ],
                'errors' => [['status' => 500, 'body' => ['error' => 'Webhook processing failed']]],
            ],
        ];

        $errorCodes = [
            ['code' => 'API_KEY_REQUIRED', 'http' => 401, 'message' => 'API key is required'],
            ['code' => 'INVALID_API_KEY', 'http' => 401, 'message' => 'Invalid or expired API key'],
            ['code' => 'API_KEY_MODE_MISMATCH', 'http' => 403, 'message' => 'API key mode mismatch'],
            ['code' => 'VALIDATION_FAILED', 'http' => 422, 'message' => 'Validation failed'],
            ['code' => 'TRANSACTION_NOT_FOUND', 'http' => 404, 'message' => 'Transaction not found'],
            ['code' => 'PAYMENT_FAILED', 'http' => 500, 'message' => 'Payment creation failed'],
            ['code' => 'REFUND_FAILED', 'http' => 500, 'message' => 'Refund creation failed'],
        ];

        return view('docs.index', [
            'baseUrl' => $baseUrl,
            'apis' => $apis,
            'errorCodes' => $errorCodes,
        ]);
    }
}

 