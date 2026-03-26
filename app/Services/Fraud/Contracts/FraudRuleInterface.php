<?php

namespace App\Services\Fraud\Contracts;

interface FraudRuleInterface
{
    /**
     * Unique rule name used for weights and audit logs.
     */
    public function name(): string;

    /**
     * @param array<string, mixed> $context
     * @return array{rule:string, triggered:bool, score:int, reason:?string, metadata:array<string, mixed>}
     */
    public function evaluate(array $context): array;
}
