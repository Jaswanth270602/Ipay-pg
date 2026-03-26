<?php

namespace App\Services\Fraud\Rules;

use App\Services\Fraud\Contracts\FraudRuleInterface;

class IpReputationRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'ip_reputation';
    }

    public function evaluate(array $context): array
    {
        $ip = (string) ($context['ip_address'] ?? '');
        $weight = (int) config('fraud.weights.ip_reputation', 25);

        if ($ip === '') {
            return $this->result(false, null, ['message' => 'Missing IP']);
        }

        // Placeholder for production integration:
        // connect to threat intel / IP reputation service.
        $blacklist = [
            '127.0.0.2',
            '10.10.10.10',
        ];

        $triggered = in_array($ip, $blacklist, true);
        $reason = $triggered ? "IP reputation check failed for {$ip}." : null;

        return $this->result($triggered, $reason, [
            'ip_address' => $ip,
            'source' => 'local_placeholder',
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
            'score' => $triggered ? ((int) ($score ?? config('fraud.weights.ip_reputation', 25))) : 0,
            'reason' => $reason,
            'metadata' => $metadata,
        ];
    }
}
