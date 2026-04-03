<?php

namespace App\Services\PaymentLinks;

use App\Models\Merchant;
use App\Models\PaymentLink;
use App\Models\PaymentLinkAttempt;
use Illuminate\Http\Request;

class PaymentLinkAttemptService
{
    /**
     * Create an attempt row for auditing.
     *
     * @param array<string, mixed> $requestPayload
     */
    public function start(?Merchant $merchant, ?string $mode, Request $request, array $requestPayload): PaymentLinkAttempt
    {
        return PaymentLinkAttempt::create([
            'merchant_id' => $merchant?->id,
            'mode' => $mode,
            'status' => 'FAILED',
            'reason' => null,
            'request_payload' => $requestPayload,
            'response_payload' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * @param array<string, mixed> $responsePayload
     */
    public function fail(PaymentLinkAttempt $attempt, string $reason, array $responsePayload): void
    {
        $attempt->update([
            'status' => 'FAILED',
            'reason' => $reason,
            'response_payload' => $responsePayload,
        ]);
    }

    /**
     * @param array<string, mixed> $responsePayload
     */
    public function succeed(PaymentLinkAttempt $attempt, PaymentLink $link, array $responsePayload): void
    {
        $attempt->update([
            'status' => 'SUCCESS',
            'reason' => null,
            'response_payload' => array_merge($responsePayload, [
                'payment_link_id' => $link->id,
                'link_token' => $link->link_token,
            ]),
        ]);
    }
}

