<?php

namespace App\Services\PaymentOrchestration;

use App\Models\AcquirerAccount;
use App\Models\Merchant;
use App\Services\AcquirerCredentialValidator;

/**
 * Resolves which acquirer account to use (Case 1: assigned, Case 2: priority + health).
 *
 * @phpstan-type FlowEntry array{acquirer: string, acquirer_account_id: int, health: string, message?: string, used_for_payment?: bool, mode?: string, source?: string, reason?: string}
 */
class AcquirerRoutingService
{
    public function __construct(
        protected AcquirerCredentialValidator $credentialValidator
    ) {}

    /**
     * @return array{account: ?AcquirerAccount, flow_trace: array<int, FlowEntry>}
     */
    public function resolve(Merchant $merchant): array
    {
        if (! $merchant->isApprovedForAcquirer()) {
            return ['account' => null, 'flow_trace' => []];
        }

        // Case 1: explicit merchant assignment — still run the same credential health check as other
        // routes so the admin trace shows passed/failed (not "skipped"). Payment routing continues
        // to use this account regardless, matching prior behaviour.
        if ($merchant->acquirer_account_id) {
            $acquirer = $merchant->acquirerAccount;
            if ($acquirer && $acquirer->is_active) {
                $result = $this->credentialValidator->validate($acquirer);
                $health = ($result['ok'] ?? false) ? 'passed' : 'failed';

                return [
                    'account' => $acquirer,
                    'flow_trace' => [[
                        'acquirer' => (string) ($acquirer->acquirer_name ?? ''),
                        'acquirer_account_id' => (int) $acquirer->id,
                        'health' => $health,
                        'reason' => 'merchant_assigned_acquirer',
                        'message' => $result['message'] ?? ($health === 'passed' ? 'OK' : 'Health check failed'),
                        'used_for_payment' => true,
                        'mode' => (string) $acquirer->mode,
                    ]],
                ];
            }
        }

        $trace = [];
        $primaryMode = $merchant->test_mode ? 'TEST' : 'LIVE';
        // Live merchants only use LIVE-mode rows; test merchants only TEST-mode rows.
        $modesToTry = [$primaryMode];

        $triedIds = [];

        foreach ($modesToTry as $tryMode) {
            $merchantLinked = $merchant->acquirerAccounts()
                ->where('mode', $tryMode)
                ->where('is_active', true)
                ->orderByRaw('COALESCE(acquirer_accounts.priority, 999999) ASC')
                ->orderBy('acquirer_accounts.id')
                ->get();

            foreach ($merchantLinked as $candidate) {
                if (in_array($candidate->id, $triedIds, true)) {
                    continue;
                }
                $triedIds[] = $candidate->id;
                $entry = $this->traceEntryFromCandidate($candidate, 'merchant_linked');
                $result = $this->credentialValidator->validate($candidate);
                if ($result['ok'] ?? false) {
                    $entry['health'] = 'passed';
                    $entry['message'] = $result['message'] ?? 'OK';
                    $entry['used_for_payment'] = true;
                    $trace[] = $entry;

                    return ['account' => $candidate, 'flow_trace' => $trace];
                }
                $entry['health'] = 'failed';
                $entry['message'] = $result['message'] ?? 'Failed';
                $trace[] = $entry;
            }

            $globalCandidates = AcquirerAccount::query()
                ->where('mode', $tryMode)
                ->where('is_active', true)
                ->when(count($triedIds) > 0, fn ($q) => $q->whereNotIn('id', $triedIds))
                ->orderByRaw('COALESCE(priority, 999999) ASC')
                ->orderBy('id')
                ->get();

            foreach ($globalCandidates as $candidate) {
                if (in_array($candidate->id, $triedIds, true)) {
                    continue;
                }
                $triedIds[] = $candidate->id;
                $entry = $this->traceEntryFromCandidate($candidate, 'platform');
                $result = $this->credentialValidator->validate($candidate);
                if ($result['ok'] ?? false) {
                    $entry['health'] = 'passed';
                    $entry['message'] = $result['message'] ?? 'OK';
                    $entry['used_for_payment'] = true;
                    $trace[] = $entry;

                    return ['account' => $candidate, 'flow_trace' => $trace];
                }
                $entry['health'] = 'failed';
                $entry['message'] = $result['message'] ?? 'Failed';
                $trace[] = $entry;
            }
        }

        return ['account' => null, 'flow_trace' => $trace];
    }

    /**
     * @return FlowEntry
     */
    protected function traceEntryFromCandidate(AcquirerAccount $candidate, string $source): array
    {
        return [
            'acquirer' => (string) ($candidate->acquirer_name ?? ''),
            'acquirer_account_id' => (int) $candidate->id,
            'mode' => (string) ($candidate->mode ?? ''),
            'source' => $source,
        ];
    }
}
