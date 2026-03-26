<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\Contracts\FraudRuleInterface;
use Illuminate\Support\Facades\Redis;

class FailedAttemptsRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'failed_attempts';
    }

    public function evaluate(array $context): array
    {
        $ip = (string) ($context['ip_address'] ?? '');
        $status = strtolower((string) ($context['payment_status'] ?? 'attempt'));
        $window = (int) config('fraud.velocity.window_seconds', 600);
        $maxFailed = (int) config('fraud.velocity.max_failed_attempts', 5);
        $bucket = (int) floor(now()->timestamp / max(1, $window));
        $weight = (int) config('fraud.weights.failed_attempts', 20);

        if ($ip === '') {
            return $this->result(false, null, ['message' => 'Missing IP']);
        }

        $key = "fraud:velocity:failed:{$ip}:{$bucket}";

        try {
            if (in_array($status, ['failed', 'cancelled'], true)) {
                $count = (int) Redis::incr($key);
                Redis::expire($key, $window + 30);
            } else {
                $count = (int) (Redis::get($key) ?? 0);
            }
        } catch (\Throwable $e) {
            return $this->result(false, null, [
                'message' => 'Redis unavailable for failed-attempt rule',
                'error' => $e->getMessage(),
            ]);
        }

        $triggered = $count > $maxFailed;
        $reason = $triggered
            ? "Too many failed attempts from IP {$ip}: {$count} in {$window}s."
            : null;

        return $this->result($triggered, $reason, [
            'ip_address' => $ip,
            'count' => $count,
            'window_seconds' => $window,
            'threshold' => $maxFailed,
            'status_observed' => $status,
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
            'score' => $triggered ? ((int) ($score ?? config('fraud.weights.failed_attempts', 20))) : 0,
            'reason' => $reason,
            'metadata' => $metadata,
        ];
    }
}
