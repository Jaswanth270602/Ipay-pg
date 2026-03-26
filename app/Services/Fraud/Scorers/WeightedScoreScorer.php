<?php

namespace App\Services\Fraud\Scorers;

class WeightedScoreScorer
{
    /**
     * @param array<int, array{rule:string, triggered:bool, score:int, reason:?string, metadata:array<string,mixed>}> $results
     * @return array{score:int,reasons:array<int,string>,events:array<int,array<string,mixed>>}
     */
    public function score(array $results): array
    {
        $total = 0;
        $reasons = [];
        $events = [];

        foreach ($results as $result) {
            $weight = max(0, min(100, (int) ($result['score'] ?? 0)));
            $triggered = (bool) ($result['triggered'] ?? false);

            if ($triggered) {
                $total += $weight;
                if (!empty($result['reason'])) {
                    $reasons[] = (string) $result['reason'];
                }
            }

            $events[] = [
                'rule_name' => (string) ($result['rule'] ?? 'unknown'),
                'triggered' => $triggered,
                'score' => $weight,
                'metadata' => (array) ($result['metadata'] ?? []),
            ];
        }

        return [
            'score' => max(0, min(100, $total)),
            'reasons' => $reasons,
            'events' => $events,
        ];
    }
}
