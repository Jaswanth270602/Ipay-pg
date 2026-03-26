<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\Contracts\FraudRuleInterface;
use Illuminate\Support\Facades\Cache;

class DeviceFingerprintRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'device_fingerprint';
    }

    public function evaluate(array $context): array
    {
        $merchantId = (int) ($context['merchant_id'] ?? 0);
        $customerKey = strtolower((string) ($context['customer_email'] ?? 'guest'));
        $fingerprint = (string) ($context['device_fingerprint'] ?? '');
        $weight = (int) config('fraud.weights.device_fingerprint', 15);

        if ($merchantId <= 0 || $fingerprint === '') {
            return $this->result(false, null, ['message' => 'Fingerprint missing']);
        }

        $cacheKey = "fraud:known_device:merchant:{$merchantId}:customer:" . md5($customerKey) . ":fp:" . md5($fingerprint);
        $seen = (bool) Cache::get($cacheKey, false);
        Cache::put($cacheKey, true, now()->addDays(30));

        $triggered = !$seen;
        $reason = $triggered ? 'New device fingerprint detected for this customer.' : null;

        return $this->result($triggered, $reason, [
            'known_device' => $seen,
        ], $weight);
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{rule:string, triggered:bool, score:int, reason:?string, metadata:array<string,mixed>}
     */
    private function result(bool $triggered, ?string $reason, array $metadata = [], ?int $score = null): array
    {
        return [
            'rule' => $this->name(),
            'triggered' => $triggered,
            'score' => $triggered ? ((int) ($score ?? config('fraud.weights.device_fingerprint', 15))) : 0,
            'reason' => $reason,
            'metadata' => $metadata,
        ];
    }
}
