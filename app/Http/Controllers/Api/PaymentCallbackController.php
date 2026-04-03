<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Orchestration callback entrypoint — verifies gateway signature and updates payments.
 * Delegates to AcquirerCallbackController; normalizes success/error payloads.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        protected AcquirerCallbackController $acquirerCallbackController
    ) {}

    /**
     * POST /api/payment/callback
     */
    public function handle(Request $request): JsonResponse
    {
        $response = $this->acquirerCallbackController->handle($request);
        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getContent(), true) ?? [];

        if ($statusCode === 401) {
            $msg = strtolower((string) ($body['message'] ?? ''));
            if (str_contains($msg, 'signature')) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid Signature',
                ], 401);
            }
        }

        if ($statusCode >= 400) {
            return response()->json([
                'status' => 'ERROR',
                'message' => $body['message'] ?? 'Callback processing failed',
            ], $statusCode);
        }

        return response()->json([
            'status' => 'SUCCESS',
            'message' => $body['message'] ?? 'Callback processed successfully',
            'transaction_id' => null,
            'amount' => null,
            'gateway' => $request->header('X-Gateway-Name'),
        ], $statusCode);
    }
}
