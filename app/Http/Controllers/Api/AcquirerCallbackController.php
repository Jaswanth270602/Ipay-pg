<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcquirerAccount;
use App\Models\ProviderResponse;
use App\Services\Acquirers\AcquirerResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Unified Acquirer Callback Controller
 * 
 * This controller handles callbacks/webhooks from ALL acquirer providers.
 * It automatically detects the provider, verifies signatures, and routes
 * to the appropriate adapter for processing.
 */
class AcquirerCallbackController extends Controller
{
    protected AcquirerResolver $resolver;

    public function __construct(AcquirerResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handle incoming webhook/callback from any acquirer.
     * 
     * Route: POST /api/webhooks/acquirer
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $this->extractSignature($request);
            $ipAddress = $request->ip();

            // Log incoming callback
            Log::info('Acquirer callback received', [
                'ip' => $ipAddress,
                'headers' => $request->headers->all(),
                'payload_keys' => array_keys($payload),
            ]);

            // Detect provider from payload
            $providerName = $this->resolver->detectProvider($payload);

            if (!$providerName) {
                Log::warning('Could not detect provider from callback payload');
                
                // Store unknown provider response for analysis
                $this->storeProviderResponse([
                    'provider' => 'unknown',
                    'event_type' => 'unknown',
                    'raw_payload' => $payload,
                    'signature' => $signature,
                    'ip_address' => $ipAddress,
                    'error_message' => 'Provider could not be detected',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Provider could not be detected',
                ], 400);
            }

            // Find active acquirer account for this provider
            // Try to match by provider name and mode
            $acquirerAccount = $this->findAcquirerAccount($providerName, $payload);

            if (!$acquirerAccount || !$acquirerAccount->is_active) {
                Log::warning('No active acquirer account found for callback', [
                    'provider' => $providerName,
                ]);

                $this->storeProviderResponse([
                    'provider' => $providerName,
                    'event_type' => 'unknown',
                    'raw_payload' => $payload,
                    'signature' => $signature,
                    'ip_address' => $ipAddress,
                    'error_message' => 'No active acquirer account found',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Acquirer account not found or inactive',
                ], 404);
            }

            // Resolve adapter
            $adapter = $this->resolver->resolve($acquirerAccount);

            // Verify webhook signature
            $signatureVerified = false;
            if ($signature) {
                $signatureVerified = $adapter->verifyWebhookSignature($payload, $signature);
                
                if (!$signatureVerified) {
                    Log::warning('Webhook signature verification failed', [
                        'provider' => $providerName,
                        'acquirer_account_id' => $acquirerAccount->id,
                    ]);

                    $this->storeProviderResponse([
                        'provider' => $providerName,
                        'acquirer_account_id' => $acquirerAccount->id,
                        'event_type' => 'unknown',
                        'raw_payload' => $payload,
                        'signature' => $signature,
                        'signature_verified' => false,
                        'ip_address' => $ipAddress,
                        'error_message' => 'Signature verification failed',
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid signature',
                    ], 401);
                }
            }

            // Normalize event type and extract reference IDs
            $eventType = $adapter->normalizeEventType($payload);
            $referenceIds = $adapter->extractReferenceIds($payload);
            $providerEventType = $payload['event'] ?? $payload['event_type'] ?? 'unknown';
            $providerStatus = $this->extractProviderStatus($payload, $providerName);
            $normalizedStatus = $providerStatus ? $adapter->normalizeStatus($providerStatus) : null;

            // Store provider response
            $providerResponse = $this->storeProviderResponse([
                'provider' => $providerName,
                'acquirer_account_id' => $acquirerAccount->id,
                'event_type' => $eventType,
                'provider_event_type' => $providerEventType,
                'raw_payload' => $payload,
                'normalized_status' => $normalizedStatus,
                'provider_status' => $providerStatus,
                'payment_id' => $referenceIds['payment_id'],
                'order_id' => $referenceIds['order_id'],
                'refund_id' => $referenceIds['refund_id'],
                'settlement_id' => $referenceIds['settlement_id'],
                'dispute_id' => $referenceIds['dispute_id'],
                'signature' => $signature,
                'signature_verified' => $signatureVerified,
                'ip_address' => $ipAddress,
            ]);

            // Process callback based on event type
            $this->processCallback($adapter, $eventType, $payload, $providerResponse);

            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Acquirer callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Store error response
            if (isset($providerName)) {
                $this->storeProviderResponse([
                    'provider' => $providerName,
                    'event_type' => 'error',
                    'raw_payload' => $request->all(),
                    'ip_address' => $request->ip(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Callback processing failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Extract signature from request headers.
     */
    protected function extractSignature(Request $request): ?string
    {
        // Razorpay uses X-Razorpay-Signature header
        if ($request->hasHeader('X-Razorpay-Signature')) {
            return $request->header('X-Razorpay-Signature');
        }

        // Check for generic signature header
        if ($request->hasHeader('X-Signature')) {
            return $request->header('X-Signature');
        }

        // Check in payload
        if ($request->has('signature')) {
            return $request->input('signature');
        }

        return null;
    }

    /**
     * Find acquirer account for provider.
     */
    protected function findAcquirerAccount(string $providerName, array $payload): ?AcquirerAccount
    {
        // Try to find by exact provider name match
        $acquirerAccount = AcquirerAccount::where('acquirer_name', $providerName)
            ->where('is_active', true)
            ->first();

        if ($acquirerAccount) {
            return $acquirerAccount;
        }

        // Try with mode-specific naming (razorpay_test, razorpay_live)
        $mode = $this->detectMode($payload);
        $providerWithMode = $providerName . '_' . strtolower($mode);

        $acquirerAccount = AcquirerAccount::where('acquirer_name', $providerWithMode)
            ->where('is_active', true)
            ->first();

        if ($acquirerAccount) {
            return $acquirerAccount;
        }

        // Try case-insensitive search
        $acquirerAccount = AcquirerAccount::whereRaw('LOWER(acquirer_name) = ?', [strtolower($providerName)])
            ->where('is_active', true)
            ->first();

        return $acquirerAccount;
    }

    /**
     * Detect mode from payload (TEST or LIVE).
     */
    protected function detectMode(array $payload): string
    {
        // Razorpay indicates test mode in payload
        if (isset($payload['payload']['payment']['entity']['method'])) {
            // Check if it's a test payment
            // Razorpay test payments have specific characteristics
        }

        // Default to TEST for safety
        return 'TEST';
    }

    /**
     * Extract provider-specific status from payload.
     */
    protected function extractProviderStatus(array $payload, string $providerName): ?string
    {
        if ($providerName === 'razorpay') {
            // Razorpay status is in payload.payload.payment.entity.status
            return $payload['payload']['payment']['entity']['status'] ?? 
                   $payload['payload']['order']['entity']['status'] ?? 
                   $payload['payload']['refund']['entity']['status'] ?? 
                   null;
        }

        return null;
    }

    /**
     * Store provider response in database.
     */
    protected function storeProviderResponse(array $data): ProviderResponse
    {
        return ProviderResponse::create($data);
    }

    /**
     * Process callback based on event type.
     */
    protected function processCallback($adapter, string $eventType, array $payload, ProviderResponse $providerResponse): void
    {
        try {
            DB::beginTransaction();

            // Route to appropriate handler based on event type
            switch ($eventType) {
                case 'payment.success':
                case 'payment.captured':
                    $this->handlePaymentSuccess($adapter, $payload, $providerResponse);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailed($adapter, $payload, $providerResponse);
                    break;

                case 'payment.authorized':
                    $this->handlePaymentAuthorized($adapter, $payload, $providerResponse);
                    break;

                case 'refund.created':
                case 'refund.success':
                    $this->handleRefund($adapter, $payload, $providerResponse);
                    break;

                case 'settlement.processed':
                    $this->handleSettlement($adapter, $payload, $providerResponse);
                    break;

                case 'dispute.created':
                case 'dispute.resolved':
                    $this->handleDispute($adapter, $payload, $providerResponse);
                    break;

                default:
                    Log::info('Unhandled event type', [
                        'event_type' => $eventType,
                        'provider_response_id' => $providerResponse->id,
                    ]);
            }

            // Mark as processed
            $providerResponse->markAsProcessed();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Callback processing failed', [
                'event_type' => $eventType,
                'provider_response_id' => $providerResponse->id,
                'error' => $e->getMessage(),
            ]);

            $providerResponse->update([
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle payment success event.
     */
    protected function handlePaymentSuccess($adapter, array $payload, ProviderResponse $providerResponse): void
    {
        // Find transaction by gateway payment ID
        $paymentId = $providerResponse->payment_id;
        
        if ($paymentId) {
            $transaction = \App\Models\Transaction::where('gateway_txn_id', $paymentId)->first();
            
            if ($transaction && $transaction->status !== 'success') {
                $transaction->update([
                    'status' => 'success',
                    'captured_at' => now(),
                    'gateway_response' => $payload,
                ]);

                $transaction->order->update(['status' => 'completed']);

                event(new \App\Events\PaymentSuccess($transaction));
            }
        }
    }

    /**
     * Handle payment failed event.
     */
    protected function handlePaymentFailed($adapter, array $payload, ProviderResponse $providerResponse): void
    {
        $paymentId = $providerResponse->payment_id;
        
        if ($paymentId) {
            $transaction = \App\Models\Transaction::where('gateway_txn_id', $paymentId)->first();
            
            if ($transaction && $transaction->status !== 'failed') {
                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $payload['payload']['payment']['entity']['error_description'] ?? 'Payment failed',
                    'gateway_response' => $payload,
                ]);

                $transaction->order->update(['status' => 'failed']);

                event(new \App\Events\PaymentFailed($transaction));
            }
        }
    }

    /**
     * Handle payment authorized event.
     */
    protected function handlePaymentAuthorized($adapter, array $payload, ProviderResponse $providerResponse): void
    {
        // Payment authorized but not yet captured
        // Update transaction status if needed
        $paymentId = $providerResponse->payment_id;
        
        if ($paymentId) {
            $transaction = \App\Models\Transaction::where('gateway_txn_id', $paymentId)->first();
            
            if ($transaction) {
                $transaction->update([
                    'status' => 'authorized',
                    'gateway_response' => $payload,
                ]);

                event(new \App\Events\PaymentAuthorized($transaction));
            }
        }
    }

    /**
     * Handle refund event.
     */
    protected function handleRefund($adapter, array $payload, ProviderResponse $providerResponse): void
    {
        $refundId = $providerResponse->refund_id;
        $paymentId = $providerResponse->payment_id;
        
        if ($refundId && $paymentId) {
            // Find refund record
            $refund = \App\Models\Refund::where('gateway_refund_id', $refundId)
                ->orWhere('gateway_txn_id', $paymentId)
                ->first();
            
            if ($refund) {
                $refund->update([
                    'status' => 'success',
                    'gateway_response' => $payload,
                    'processed_at' => now(),
                ]);

                event(new \App\Events\RefundCreated($refund));
            }
        }
    }

    /**
     * Handle settlement event.
     */
    protected function handleSettlement($adapter, array $payload, ProviderResponse $providerResponse): void
    {
        // Handle settlement processing
        // This can trigger settlement creation in the settlement engine
        Log::info('Settlement event received', [
            'settlement_id' => $providerResponse->settlement_id,
            'provider_response_id' => $providerResponse->id,
        ]);
    }

    /**
     * Handle dispute event.
     */
    protected function handleDispute($adapter, array $payload, ProviderResponse $providerResponse): void
    {
        // Handle dispute creation/resolution
        Log::info('Dispute event received', [
            'dispute_id' => $providerResponse->dispute_id,
            'provider_response_id' => $providerResponse->id,
        ]);
    }
}

