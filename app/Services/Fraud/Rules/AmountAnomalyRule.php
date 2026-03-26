<?php

namespace App\Services\Fraud\Rules;

use App\Models\Transaction;
use App\Services\Fraud\Contracts\FraudRuleInterface;
use Illuminate\Support\Facades\Cache;

class AmountAnomalyRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'amount_anomaly';
    }

    public function evaluate(array $context): array
    {
        $merchantId = (int) ($context['merchant_id'] ?? 0);
        $customerEmail = strtolower((string) ($context['customer_email'] ?? ''));
        $amount = (float) ($context['amount'] ?? 0);
        $weight = (int) config('fraud.weights.amount_anomaly', 15);

        if ($merchantId <= 0 || $amount <= 0 || $customerEmail === '') {
            return $this->result(false, null, ['message' => 'Insufficient context for amount anomaly']);
        }

        $ttl = (int) config('fraud.cache_ttl_seconds.user_average', 600);
        $cacheKey = "fraud:avg:merchant:{$merchantId}:email:" . md5($customerEmail);
        $avg = Cache::remember($cacheKey, $ttl, function () use ($merchantId, $customerEmail) {
            return (float) Transaction::query()
                ->where('merchant_id', $merchantId)
                ->where('status', 'success')
                ->where('customer_email', $customerEmail)
                ->avg('amount');
        });

        if ($avg <= 0) {
            return $this->result(false, null, ['average' => 0, 'message' => 'No baseline yet']);
        }

        $ratio = $amount / max(0.01, $avg);
        $triggered = $ratio >= 3.0;
        $reason = $triggered
            ? 'Amount anomaly detected: transaction exceeds 3x historical average.'
            : null;

        return $this->result($triggered, $reason, [
            'average_amount' => round($avg, 2),
            'current_amount' => round($amount, 2),
            'ratio' => round($ratio, 2),
            'threshold_ratio' => 3.0,
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
            'score' => $triggered ? ((int) ($score ?? config('fraud.weights.amount_anomaly', 15))) : 0,
            'reason' => $reason,
            'metadata' => $metadata,
        ];
    }
}
