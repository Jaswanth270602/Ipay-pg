<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\Contracts\FraudRuleInterface;
use Illuminate\Support\Facades\Redis;

class VelocityIpRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'velocity_ip';
    }

    public function evaluate(array $context): array
    {
        $ip = (string) ($context['ip_address'] ?? '');
        if ($ip === '') {
            return $this->result(false, null, ['message' => 'Missing IP address']);
        }

        $window = (int) config('fraud.velocity.window_seconds', 600);
        $maxTxns = (int) config('fraud.velocity.max_txns_per_ip', 8);
        $weight = (int) config('fraud.weights.velocity_ip', 20);
        $bucket = (int) floor(now()->timestamp / max(1, $window));
        $key = "fraud:velocity:ip:{$ip}:{$bucket}";

        try {
            $count = (int) Redis::incr($key);
            Redis::expire($key, $window + 30);
        } catch (\Throwable $e) {
            return $this->result(false, null, [
                'message' => 'Redis unavailable for velocity rule',
                'error' => $e->getMessage(),
            ]);
        }

        $triggered = $count > $maxTxns;
        $reason = $triggered
            ? "High velocity from IP {$ip}: {$count} attempts in {$window}s."
            : null;

        return $this->result($triggered, $reason, [
            'ip_address' => $ip,
            'count' => $count,
            'window_seconds' => $window,
            'threshold' => $maxTxns,
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
            'score' => $triggered ? ((int) ($score ?? config('fraud.weights.velocity_ip', 20))) : 0,
            'reason' => $reason,
            'metadata' => $metadata,
        ];
    }
}
