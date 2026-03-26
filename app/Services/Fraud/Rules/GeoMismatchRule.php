<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\Contracts\FraudRuleInterface;
use Illuminate\Support\Facades\Cache;

class GeoMismatchRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'geo_mismatch';
    }

    public function evaluate(array $context): array
    {
        $merchantId = (int) ($context['merchant_id'] ?? 0);
        $customerKey = strtolower((string) ($context['customer_email'] ?? 'guest'));
        $currentCountry = strtoupper(trim((string) ($context['country'] ?? '')));
        $weight = (int) config('fraud.weights.geo_mismatch', 15);

        if ($merchantId <= 0 || $currentCountry === '') {
            return $this->result(false, null, ['message' => 'Country data unavailable']);
        }

        $cacheKey = "fraud:last_country:merchant:{$merchantId}:customer:" . md5($customerKey);
        $ttl = (int) config('fraud.cache_ttl_seconds.country_last_seen', 86400);
        $lastCountry = (string) Cache::get($cacheKey, '');
        Cache::put($cacheKey, $currentCountry, $ttl);

        $triggered = $lastCountry !== '' && $lastCountry !== $currentCountry;
        $reason = $triggered
            ? "Geo mismatch detected: last {$lastCountry}, current {$currentCountry}."
            : null;

        return $this->result($triggered, $reason, [
            'last_country' => $lastCountry,
            'current_country' => $currentCountry,
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
            'score' => $triggered ? ((int) ($score ?? config('fraud.weights.geo_mismatch', 15))) : 0,
            'reason' => $reason,
            'metadata' => $metadata,
        ];
    }
}
