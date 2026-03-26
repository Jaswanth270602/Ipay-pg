<?php

namespace App\Services\Fraud;

class DecisionService
{
    public function decide(int $score): string
    {
        $allowMax = (int) config('fraud.thresholds.allow_max', 30);
        $reviewMax = (int) config('fraud.thresholds.review_max', 60);

        if ($score <= $allowMax) {
            return 'allow';
        }

        if ($score <= $reviewMax) {
            return 'review';
        }

        return 'block';
    }
}
