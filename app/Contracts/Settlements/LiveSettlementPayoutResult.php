<?php

namespace App\Contracts\Settlements;

final class LiveSettlementPayoutResult
{
    public function __construct(
        public bool $success,
        public ?string $bankReference = null,
        public ?string $message = null,
    ) {}
}
