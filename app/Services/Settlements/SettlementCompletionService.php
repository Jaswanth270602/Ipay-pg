<?php

namespace App\Services\Settlements;

use App\Models\Notification;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Finalizes settlement status on batch + line items and notifies merchants.
 * Live bank payouts are not executed here — integrate a bank/acquirer driver later.
 */
class SettlementCompletionService
{
    public function markSettlementsCompleted(
        array $settlementIds,
        ?int $merchantId = null,
        ?string $bankReference = null,
        bool $notify = true
    ): int {
        if ($settlementIds === []) {
            return 0;
        }

        $q = Settlement::query()->whereIn('id', $settlementIds);
        if ($merchantId !== null) {
            $q->where('merchant_id', $merchantId);
        }

        $settlements = $q->get();
        foreach ($settlements as $settlement) {
            $this->applySingleSettlementCompleted($settlement, $bankReference, $notify);
        }

        return $settlements->count();
    }

    public function applySingleSettlementCompleted(Settlement $settlement, ?string $bankReference, bool $notify): void
    {
        DB::transaction(function () use ($settlement, $bankReference) {
            $settlement->refresh();
            $ref = $bankReference ?? $settlement->bank_reference;

            $settlement->update([
                'settlement_status' => 'settled',
                'status' => 'completed',
                'processed_at' => now(),
                'bank_reference' => $ref,
            ]);

            DB::table('settlement_details')
                ->where('settlement_id', $settlement->id)
                ->update([
                    'settlement_status' => 'settled',
                    'settlement_qualifier' => 'settled',
                    'bank_reference' => $ref,
                    'updated_at' => now(),
                ]);

            // Keep transaction-level settlement status in sync for vendor/merchant dashboards.
            DB::table('transactions')
                ->where('settlement_id', $settlement->id)
                ->update([
                    'settlement_status' => 'settled',
                    'settled_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        if ($notify) {
            $fresh = $settlement->fresh('merchant');
            $this->notifyMerchants($fresh, $fresh->bank_reference);
        }
    }

    /**
     * Pending test settlements older than the cooling-off period are auto-marked settled (no acquirer response).
     */
    public function autoCompleteEligibleTestSettlements(): int
    {
        $ids = Settlement::query()
            ->where('test_mode', true)
            ->where('settlement_status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->pluck('id')
            ->all();

        $count = 0;
        foreach ($ids as $id) {
            $s = Settlement::find($id);
            if (! $s) {
                continue;
            }
            $ref = 'TST-'.$s->settlement_id.'-'.now()->format('YmdHis');
            $this->applySingleSettlementCompleted($s, $ref, true);
            $count++;
        }

        return $count;
    }

    protected function notifyMerchants(Settlement $settlement, ?string $bankReference): void
    {
        try {
            $settlement->loadMissing('merchant');
            $merchant = $settlement->merchant;
            if (! $merchant) {
                return;
            }

            $modeLabel = $settlement->test_mode ? 'Test' : 'Live';
            $amount = number_format((float) ($settlement->payout_amount ?? $settlement->net_amount), 2);
            $curr = $settlement->currency ?? 'INR';
            $suffix = $bankReference ? ' Ref: '.$bankReference.'.' : '';

            $msg = sprintf(
                'Settlement completed (%s): %s %s — %s.%s',
                $modeLabel,
                $curr,
                $amount,
                $settlement->settlement_id,
                $suffix
            );

            $url = route('merchant.settlements.summary');

            $users = User::query()
                ->where('status', 'active')
                ->where('merchant_id', $merchant->id)
                ->whereHas('role', function ($q) {
                    $q->where('name', 'merchant');
                })
                ->get();

            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'role' => 'merchant',
                    'message' => $msg,
                    'is_read' => false,
                    'url' => $url,
                    'order_url' => null,
                    'meta' => [
                        'type' => 'settlement_completed',
                        'settlement_id' => $settlement->settlement_id,
                        'test_mode' => (bool) $settlement->test_mode,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify merchants of settlement completion', [
                'settlement_id' => $settlement->settlement_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
