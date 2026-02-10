<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public WebhookEvent $webhookEvent;
    public int $tries = 5;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(WebhookEvent $webhookEvent)
    {
        $this->webhookEvent = $webhookEvent;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $merchant = $this->webhookEvent->merchant;

            // Determine mode (check payload for test_mode or use merchant default)
            $isTestMode = $this->webhookEvent->payload['payload']['payment']['entity']['test_mode'] ?? 
                         $this->webhookEvent->payload['payload']['order']['entity']['test_mode'] ?? 
                         $merchant->test_mode ?? true;

            // Add mode information to payload if not present
            if (!isset($this->webhookEvent->payload['mode'])) {
                $payload = $this->webhookEvent->payload;
                $payload['mode'] = $isTestMode ? 'test' : 'live';
                $this->webhookEvent->update(['payload' => $payload]);
            }

            // Generate signature for webhook
            $signature = $this->generateSignature(
                $this->webhookEvent->payload,
                $merchant->webhook_secret
            );

            // Build headers
            $headers = [
                'Content-Type' => 'application/json',
                'X-BadliCash-Signature' => $signature,
                'X-BadliCash-Event' => $this->webhookEvent->event_type,
                'X-BadliCash-Delivery-ID' => (string) $this->webhookEvent->id,
                'X-BadliCash-Mode' => $isTestMode ? 'test' : 'live',
            ];

            // Send webhook
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($this->webhookEvent->webhook_url, $this->webhookEvent->payload);

            if ($response->successful()) {
                $this->webhookEvent->markAsDelivered();
                
                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $this->webhookEvent->id,
                    'event_type' => $this->webhookEvent->event_type,
                    'mode' => $isTestMode ? 'test' : 'live',
                ]);
            } else {
                throw new \Exception("Webhook delivery failed with status: {$response->status()}");
            }

        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'webhook_id' => $this->webhookEvent->id,
                'error' => $e->getMessage(),
                'attempt' => $this->webhookEvent->attempt_count + 1,
            ]);

            $this->webhookEvent->incrementAttempt($e->getMessage());

            // Retry if not exceeded max attempts
            if ($this->webhookEvent->shouldRetry()) {
                $delay = $this->webhookEvent->next_retry_at->diffInSeconds(now());
                self::dispatch($this->webhookEvent)->delay($delay);
            }
        }
    }

    /**
     * Generate HMAC signature for webhook.
     */
    protected function generateSignature(array $payload, ?string $secret): string
    {
        $secret = $secret ?? config('app.key');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}

