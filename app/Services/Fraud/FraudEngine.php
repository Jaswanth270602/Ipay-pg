<?php

namespace App\Services\Fraud;

use App\Models\FraudEvent;
use App\Models\FraudTransaction;
use App\Services\Fraud\Contracts\FraudRuleInterface;
use App\Services\Fraud\Rules\AmountAnomalyRule;
use App\Services\Fraud\Rules\DeviceFingerprintRule;
use App\Services\Fraud\Rules\FailedAttemptsRule;
use App\Services\Fraud\Rules\GeoMismatchRule;
use App\Services\Fraud\Rules\HistoricalFlagsRule;
use App\Services\Fraud\Rules\IpReputationRule;
use App\Services\Fraud\Rules\VelocityIpRule;
use App\Services\Fraud\Scorers\WeightedScoreScorer;
use Illuminate\Support\Facades\DB;

class FraudEngine
{
    /**
     * @var array<int, FraudRuleInterface>
     */
    private array $rules;

    public function __construct(
        private readonly WeightedScoreScorer $scorer,
        private readonly DecisionService $decisionService
    ) {
        // Default rule set; rules are independently reusable.
        $this->rules = [
            app(IpReputationRule::class),
            app(VelocityIpRule::class),      // sample velocity rule (Redis)
            app(FailedAttemptsRule::class),
            app(DeviceFingerprintRule::class),
            app(AmountAnomalyRule::class),
            app(GeoMismatchRule::class),     // sample geo mismatch rule
            app(HistoricalFlagsRule::class),
        ];
    }

    /**
     * @param array<string,mixed> $context
     * @return array{score:int,decision:'allow'|'review'|'block',reasons:array<int,string>}
     */
    public function evaluate(array $context): array
    {
        $results = [];
        foreach ($this->rules as $rule) {
            $results[] = $rule->evaluate($context);
        }

        $scored = $this->scorer->score($results);
        $decision = $this->decisionService->decide((int) $scored['score']);

        $payload = [
            'score' => (int) $scored['score'],
            'decision' => $decision,
            'reasons' => array_values(array_unique($scored['reasons'])),
        ];

        $this->persist($context, $payload, $scored['events']);

        return $payload;
    }

    /**
     * @param array<string,mixed> $context
     * @param array{score:int,decision:string,reasons:array<int,string>} $payload
     * @param array<int,array<string,mixed>> $events
     */
    private function persist(array $context, array $payload, array $events): void
    {
        DB::transaction(function () use ($context, $payload, $events): void {
            $fraudTxn = FraudTransaction::create([
                'transaction_id' => $context['transaction_id'] ?? null,
                'merchant_id' => $context['merchant_id'] ?? null,
                'user_id' => $context['user_id'] ?? null,
                'risk_score' => $payload['score'],
                'decision' => $payload['decision'],
                'reasons' => $payload['reasons'],
                'context' => [
                    'ip_address' => $context['ip_address'] ?? null,
                    'customer_email' => $context['customer_email'] ?? null,
                    'device_fingerprint' => $context['device_fingerprint'] ?? null,
                    'country' => $context['country'] ?? null,
                    'amount' => $context['amount'] ?? null,
                ],
            ]);

            foreach ($events as $event) {
                FraudEvent::create([
                    'fraud_transaction_id' => $fraudTxn->id,
                    'rule_name' => (string) ($event['rule_name'] ?? 'unknown'),
                    'triggered' => (bool) ($event['triggered'] ?? false),
                    'score' => (int) ($event['score'] ?? 0),
                    'metadata' => (array) ($event['metadata'] ?? []),
                ]);
            }
        });
    }
}
