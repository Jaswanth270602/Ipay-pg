<?php

namespace App\Services\Fraud\Rules;

use App\Models\FraudAlert;
use App\Services\Fraud\Contracts\FraudRuleInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HistoricalFlagsRule implements FraudRuleInterface
{
    public function name(): string
    {
        return 'historical_flags';
    }

    public function evaluate(array $context): array
    {
        $merchantId = (int) ($context['merchant_id'] ?? 0);
        $weight = (int) config('fraud.weights.historical_flags', 10);

        if ($merchantId <= 0) {
            return $this->result(false, null, ['message' => 'Missing merchant_id']);
        }

        $ttl = (int) config('fraud.cache_ttl_seconds.historical_flags', 300);
        $cacheKey = "fraud:history:merchant:{$merchantId}";
        $counts = Cache::remember($cacheKey, $ttl, function () use ($merchantId) {
            $alerts = (int) FraudAlert::query()
                ->where('merchant_id', $merchantId)
                ->whereIn('severity', ['high', 'critical'])
                ->whereIn('status', ['open', 'investigating'])
                ->count();

            $chargebacks = 0;
            if (DB::getSchemaBuilder()->hasTable('chargebacks')) {
                $chargebacks = (int) DB::table('chargebacks')
                    ->where('merchant_id', $merchantId)
                    ->count();
            }

            return [
                'open_alerts' => $alerts,
                'chargebacks' => $chargebacks,
            ];
        });

        $triggered = (($counts['open_alerts'] ?? 0) > 0) || (($counts['chargebacks'] ?? 0) > 0);
        $reason = $triggered
            ? 'Historical risk flags found (open alerts or chargebacks).'
            : null;

        return $this->result($triggered, $reason, $counts, $weight);
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
            'score' => $triggered ? ((int) ($score ?? config('fraud.weights.historical_flags', 10))) : 0,
            'reason' => $reason,
            'metadata' => $metadata,
        ];
    }
}
