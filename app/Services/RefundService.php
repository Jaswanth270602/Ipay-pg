<?php

namespace App\Services;

use App\Events\RefundCreated;
use App\Models\Notification;
use App\Models\PgRefundApproval;
use App\Models\Refund;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankProviders\BankProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RefundService
{
    /** Amounts at or above this require admin approval before processing (same currency as refund). */
    public const APPROVAL_THRESHOLD = 10000.0;

    protected BankProviderInterface $bankProvider;

    public function __construct(BankProviderInterface $bankProvider)
    {
        $this->bankProvider = $bankProvider;
    }

    /**
     * Create a refund for a transaction.
     *
     * @param  string|null  $currency  ISO 4217 code; must match transaction currency when provided.
     */
    public function createRefund(
        Transaction $transaction,
        float $amount,
        User $initiator,
        ?string $reason = null,
        ?string $currency = null,
        ?string $strategy = null,
        ?int $vendorId = null
    ): Refund
    {
        return DB::transaction(function () use ($transaction, $amount, $initiator, $reason, $currency) {
            $refundableAmount = $transaction->refundableAmount();

            if ($amount > $refundableAmount) {
                throw new \Exception("Refund amount exceeds refundable amount. Maximum: {$refundableAmount}");
            }

            if ($amount <= 0) {
                throw new \Exception('Refund amount must be greater than zero');
            }

            $currencyCode = strtoupper($currency ?? $transaction->currency);
            $refundStrategy = $strategy ?? 'proportional';

            $mode = $this->refundMode($transaction);

            if ($this->requiresAdminApproval($amount)) {
                    $refund = Refund::create([
                    'transaction_id' => $transaction->id,
                    'merchant_id' => $transaction->merchant_id,
                        'vendor_id' => $vendorId,
                    'refund_id' => Refund::generateRefundId(),
                    'amount' => $amount,
                    'currency' => $currencyCode,
                    'status' => 'pending_approval',
                    'mode' => $mode,
                        'refund_strategy' => $refundStrategy,
                    'reason' => $reason,
                    'initiated_by' => $initiator->id,
                    'is_partial' => $amount < $transaction->amount,
                ]);

                $this->createPgRefundApprovalRequest($refund, $initiator, $transaction);

                return $refund;
            }

            if ($this->isRefundTestBehavior($transaction)) {
                $refund = Refund::create([
                    'transaction_id' => $transaction->id,
                    'merchant_id' => $transaction->merchant_id,
                    'vendor_id' => $vendorId,
                    'refund_id' => Refund::generateRefundId(),
                    'amount' => $amount,
                    'currency' => $currencyCode,
                    'status' => 'completed',
                    'mode' => 'test',
                    'refund_strategy' => $refundStrategy,
                    'reason' => $reason,
                    'initiated_by' => $initiator->id,
                    'is_partial' => $amount < $transaction->amount,
                    'gateway_response' => [
                        'success' => true,
                        'mode' => 'test',
                        'message' => 'Simulated refund completed in test mode.',
                    ],
                    'processed_at' => now(),
                ]);

                event(new RefundCreated($refund));

                return $refund;
            }

            $refund = Refund::create([
                'transaction_id' => $transaction->id,
                'merchant_id' => $transaction->merchant_id,
                'vendor_id' => $vendorId,
                'refund_id' => Refund::generateRefundId(),
                'amount' => $amount,
                'currency' => $currencyCode,
                'status' => 'pending_processing',
                'mode' => $mode,
                'refund_strategy' => $refundStrategy,
                'reason' => $reason,
                'initiated_by' => $initiator->id,
                'is_partial' => $amount < $transaction->amount,
            ]);

            $this->initiateLiveRefundProcessing($refund, $transaction);

            return $refund;
        });
    }

    public function requiresAdminApproval(float $amount): bool
    {
        return $amount >= self::APPROVAL_THRESHOLD;
    }

    /**
     * Test-style refunds: merchant/sandbox transaction or gateway not in live mode.
     */
    public function isRefundTestBehavior(Transaction $transaction): bool
    {
        return $transaction->test_mode || GatewayModeService::isTest();
    }

    /**
     * Stored on refund: "test" | "live" for reporting and approval outcomes.
     */
    public function refundMode(Transaction $transaction): string
    {
        return $this->isRefundTestBehavior($transaction) ? 'test' : 'live';
    }

    protected function createPgRefundApprovalRequest(Refund $refund, User $initiator, Transaction $transaction): void
    {
        $merchant = $transaction->merchant;

        $approval = PgRefundApproval::create([
            'created_by' => $initiator->id,
            'merchant_id' => $merchant->id,
            'merchant_name' => $merchant->name,
            'model_id' => $refund->id,
            'model_name' => 'Refund',
            'operation' => 'refund_create',
            'previous_changes' => null,
            'changes' => [
                'refund_id' => $refund->refund_id,
                'amount' => (float) $refund->amount,
                'currency' => $refund->currency,
                'transaction_id' => $transaction->txn_id,
                'mode' => $refund->mode,
            ],
            'is_approved' => 'pending',
        ]);

        $this->notifyAdminsOfPgRefundApprovalRequest($refund, $approval);
    }

    /**
     * Notify all active admins that a large refund needs approval (navbar bell).
     */
    protected function notifyAdminsOfPgRefundApprovalRequest(Refund $refund, PgRefundApproval $approval): void
    {
        try {
            $refund->loadMissing('merchant');
            $merchantName = $refund->merchant?->name ?? 'Unknown merchant';
            $message = sprintf(
                'Refund approval required: %s %s — %s (Refund %s)',
                $refund->currency,
                number_format((float) $refund->amount, 2),
                $merchantName,
                $refund->refund_id
            );

            $adminListUrl = route('admin.approvals.pg-refunds');

            $admins = User::query()
                ->where('status', 'active')
                ->whereHas('role', function ($q) {
                    $q->where('name', 'admin');
                })
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'role' => 'admin',
                    'message' => $message,
                    'is_read' => false,
                    'url' => $adminListUrl,
                    'order_url' => null,
                    'meta' => [
                        'type' => 'pg_refund_approval',
                        'pg_refund_approval_id' => $approval->id,
                        'refund_id' => $refund->refund_id,
                        'merchant_id' => $refund->merchant_id,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins of PG refund approval', [
                'refund_id' => $refund->refund_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * After admin approves a large refund: test → completed; live → pending_processing + bank initiation.
     */
    public function processRefundApprovedByAdmin(Refund $refund): void
    {
        if ($refund->status !== 'pending_approval') {
            return;
        }

        $transaction = $refund->transaction;
        $mode = $refund->mode ?: $this->refundMode($transaction);

        if ($mode === 'test') {
            $refund->update([
                'status' => 'completed',
                'gateway_response' => array_merge($refund->gateway_response ?? [], [
                    'success' => true,
                    'message' => 'Refund approved and completed in test mode.',
                ]),
                'processed_at' => now(),
            ]);
            event(new RefundCreated($refund));

            // Apply vendor-side effects (in test mode we treat completion as final)
            app(\App\Services\RefundSplitService::class)->applyForCompletedRefund($refund);
        } else {
            $refund->update(['status' => 'pending_processing']);
            $this->initiateLiveRefundProcessing($refund, $transaction);
        }

        $refund->refresh();
        $this->notifyMerchantsOfPgRefundDecision($refund, 'approved');
    }

    /**
     * When admin rejects, mark refund as cancelled.
     */
    public function cancelRefundPendingApproval(Refund $refund): void
    {
        if ($refund->status !== 'pending_approval') {
            return;
        }

        $refund->update(['status' => 'cancelled']);
        $refund->refresh();
        $this->notifyMerchantsOfPgRefundDecision($refund, 'rejected');
    }

    /**
     * Notify all active merchant users for this refund when admin approves or rejects the PG approval request.
     */
    protected function notifyMerchantsOfPgRefundDecision(Refund $refund, string $decision): void
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            return;
        }

        try {
            $refund->loadMissing('merchant');
            $refundsUrl = route('merchant.refunds.index');

            $amountStr = number_format((float) $refund->amount, 2);
            if ($decision === 'approved') {
                $detail = match ($refund->status) {
                    'completed' => 'It has been completed (test / sandbox).',
                    'failed' => 'It was approved but processing failed—check Refunds for details.',
                    'pending_processing', 'processing' => 'It is being processed with the payment gateway.',
                    default => 'See Refunds for the latest status.',
                };
                $message = sprintf(
                    'Refund approved by admin: %s %s — %s. %s',
                    $refund->currency,
                    $amountStr,
                    $refund->refund_id,
                    $detail
                );
            } else {
                $message = sprintf(
                    'Refund request rejected by admin: %s %s — %s. The refund has been cancelled.',
                    $refund->currency,
                    $amountStr,
                    $refund->refund_id
                );
            }

            $users = User::query()
                ->where('status', 'active')
                ->where('merchant_id', $refund->merchant_id)
                ->whereHas('role', function ($q) {
                    $q->where('name', 'merchant');
                })
                ->get();

            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'role' => 'merchant',
                    'message' => $message,
                    'is_read' => false,
                    'url' => $refundsUrl,
                    'order_url' => null,
                    'meta' => [
                        'type' => 'pg_refund_approval_result',
                        'refund_id' => $refund->refund_id,
                        'decision' => $decision,
                        'merchant_id' => $refund->merchant_id,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify merchants of PG refund decision', [
                'refund_id' => $refund->refund_id ?? null,
                'decision' => $decision,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function initiateLiveRefundProcessing(Refund $refund, Transaction $transaction): void
    {
        try {
            $result = $this->bankProvider->processRefund(
                $transaction->gateway_txn_id ?? $transaction->txn_id,
                (float) $refund->amount
            );

            if (! empty($result['success'])) {
                $refund->update([
                    'gateway_response' => $result,
                    'gateway_refund_id' => $result['refund_id'] ?? $result['gateway_refund_id'] ?? null,
                ]);

                return;
            }

            $refund->update([
                'status' => 'failed',
                'gateway_response' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Refund processing error', [
                'refund_id' => $refund->refund_id,
                'transaction_id' => $transaction->txn_id,
                'error' => $e->getMessage(),
            ]);

            $refund->update([
                'status' => 'failed',
                'gateway_response' => array_merge($refund->gateway_response ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }
    }

    /**
     * Resolve refund from PG approval row (if linked to Refund model).
     */
    public function findRefundForPgApproval(PgRefundApproval $approval): ?Refund
    {
        if (($approval->model_name ?? '') !== 'Refund' || ! $approval->model_id) {
            return null;
        }

        return Refund::find($approval->model_id);
    }

    /**
     * Unified refund creator used by merchant form and bulk upload.
     * This reuses createRefund() after applying the same lookup/validation flow.
     *
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function createRefundByTransactionId(
        User $initiator,
        string $transactionId,
        float $amount,
        ?string $reason = null,
        ?int $merchantId = null,
        ?bool $testMode = null
    ): Refund {
        $query = Transaction::query()->where('txn_id', $transactionId);
        if ($merchantId !== null) {
            $query->where('merchant_id', $merchantId);
        }
        if ($testMode !== null) {
            $query->where('test_mode', $testMode);
        }

        $transaction = $query->first();
        if (!$transaction) {
            throw new ModelNotFoundException('Transaction not found');
        }

        if ($transaction->status !== 'success') {
            throw new \Exception('Cannot refund unsuccessful transaction. Only successful transactions can be refunded.');
        }

        return $this->createRefund($transaction, $amount, $initiator, $reason);
    }

    /**
     * Process a full refund.
     */
    public function fullRefund(Transaction $transaction, User $initiator, ?string $reason = null): Refund
    {
        return $this->createRefund($transaction, $transaction->amount, $initiator, $reason);
    }

    /**
     * Process a partial refund.
     */
    public function partialRefund(Transaction $transaction, float $amount, User $initiator, ?string $reason = null): Refund
    {
        return $this->createRefund($transaction, $amount, $initiator, $reason);
    }
}
