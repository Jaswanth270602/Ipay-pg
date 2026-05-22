<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChargebackCreationService
{
    /** @var list<string> */
    private array $eligibleTxnStatuses = ['success', 'captured'];

    /**
     * @return array<string, mixed>
     */
    public function createForMerchant(Merchant $merchant, array $input, bool $notify = true): array
    {
        $requestId = trim((string) ($input['chargeback_request_id'] ?? ''));
        $txnRef = trim((string) ($input['transaction_id'] ?? ''));
        $amount = $input['chargeback_amount'] ?? $input['amount'] ?? null;
        $status = $this->normalizeStatus((string) ($input['chargeback_status'] ?? $input['status'] ?? 'pending'));
        $notes = trim((string) ($input['notes'] ?? ''));
        $targetDate = ! empty($input['target_date']) ? Carbon::parse($input['target_date']) : null;

        if ($requestId === '') {
            $requestId = $this->generateRequestId($merchant);
        }

        $transaction = $this->resolveTransaction($merchant, $txnRef);
        $amountFloat = $this->validateAmount($amount, $transaction);

        if (DB::table('chargebacks')->where('chargeback_request_id', $requestId)->exists()) {
            throw ValidationException::withMessages([
                'chargeback_request_id' => ['This chargeback request ID already exists.'],
            ]);
        }

        $this->assertChargebackCapacity($transaction, $amountFloat);

        $gw = is_array($transaction->gateway_response) ? $transaction->gateway_response : [];
        $refunded = $this->transactionHasRefund($transaction->id);

        $insert = [
            'merchant_id' => $merchant->id,
            'transaction_id' => $transaction->id,
            'chargeback_request_id' => $requestId,
            'chargeback_amount' => round($amountFloat, 2),
            'chargeback_status' => $status,
            'refunded_or_not' => $refunded ? 'Yes' : 'No',
            'contested' => $status === 'contested' ? 'Yes' : 'No',
            'is_dispute' => 'Yes',
            'debit_merchant' => in_array($status, ['lost'], true) ? 'Yes' : 'No',
            'account_id' => $gw['account_id'] ?? $transaction->gateway_txn_id ?? null,
            'account_id_descript' => $transaction->gateway ?? null,
            'target_date' => ($targetDate ?? now()->addDays(14))->toDateString(),
            'notes' => $notes !== '' ? $notes : 'Chargeback registered — respond before target date.',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('chargebacks', 'test_mode')) {
            $insert['test_mode'] = (bool) $transaction->test_mode;
        }

        $id = (int) DB::table('chargebacks')->insertGetId($insert);

        if ($notify) {
            $this->notifyMerchant($merchant, $id, $requestId, $amountFloat, $transaction->currency ?? 'INR', 'created');
        }

        return $this->formatChargebackRow($id, $merchant);
    }

    /**
     * Create or update from acquirer dispute webhook (Razorpay/Cashfree-style).
     *
     * @param  array<string, mixed>  $payload
     */
    public function upsertFromAcquirerDispute(
        Transaction $transaction,
        string $acquirerDisputeId,
        float $amount,
        string $eventType,
        array $payload = []
    ): void {
        $merchant = $transaction->merchant ?? Merchant::query()->find($transaction->merchant_id);
        if (! $merchant) {
            return;
        }

        $requestId = 'ACQ_'.$acquirerDisputeId;
        $existing = DB::table('chargebacks')->where('chargeback_request_id', $requestId)->first();

        $status = match (true) {
            str_contains(strtolower($eventType), 'resolved') => 'processing',
            default => 'pending',
        };

        $entity = $payload['payload']['dispute']['entity'] ?? [];
        if (is_array($entity) && ! empty($entity['status'])) {
            $status = $this->mapAcquirerDisputeStatus((string) $entity['status']);
        }

        if ($existing) {
            DB::table('chargebacks')->where('id', $existing->id)->update([
                'chargeback_status' => $status,
                'chargeback_amount' => round($amount > 0 ? $amount : (float) $existing->chargeback_amount, 2),
                'contested' => $status === 'contested' ? 'Yes' : $existing->contested,
                'debit_merchant' => $status === 'lost' ? 'Yes' : $existing->debit_merchant,
                'updated_at' => now(),
            ]);

            $this->notifyMerchant($merchant, (int) $existing->id, $requestId, (float) $existing->chargeback_amount, $transaction->currency ?? 'INR', 'updated');

            return;
        }

        try {
            $this->createForMerchant($merchant, [
                'chargeback_request_id' => $requestId,
                'transaction_id' => $transaction->txn_id,
                'chargeback_amount' => $amount > 0 ? $amount : $transaction->amount,
                'chargeback_status' => $status,
                'notes' => 'Auto-created from acquirer dispute webhook ('.$eventType.').',
            ], true);
        } catch (ValidationException $e) {
            Log::warning('Acquirer dispute webhook could not create chargeback', [
                'txn_id' => $transaction->txn_id,
                'dispute_id' => $acquirerDisputeId,
                'errors' => $e->errors(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lookupTransactionForMerchant(Merchant $merchant, string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        try {
            $transaction = $this->resolveTransaction($merchant, $reference);
        } catch (ValidationException) {
            return null;
        }

        $gw = is_array($transaction->gateway_response) ? $transaction->gateway_response : [];
        $existingCb = (float) DB::table('chargebacks')
            ->where('transaction_id', $transaction->id)
            ->sum('chargeback_amount');

        return [
            'txn_id' => $transaction->txn_id,
            'transaction_id' => $transaction->id,
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency ?? 'INR',
            'status' => $transaction->status,
            'payment_method' => $transaction->payment_method,
            'captured_at' => $transaction->captured_at?->toIso8601String(),
            'gateway' => $transaction->gateway,
            'gateway_txn_id' => $transaction->gateway_txn_id,
            'account_id' => $gw['account_id'] ?? null,
            'refunded' => $this->transactionHasRefund($transaction->id),
            'existing_chargeback_total' => round($existingCb, 2),
            'remaining_chargeback_capacity' => max(0, round((float) $transaction->amount - $existingCb, 2)),
            'suggested_request_id' => $this->generateRequestId($merchant),
            'suggested_amount' => max(0, round((float) $transaction->amount - $existingCb, 2)),
            'suggested_target_date' => now()->addDays(14)->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contestChargeback(Merchant $merchant, int $chargebackId, ?string $notes = null): array
    {
        $row = DB::table('chargebacks')
            ->where('id', $chargebackId)
            ->where('merchant_id', $merchant->id)
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'id' => ['Chargeback not found.'],
            ]);
        }

        $status = strtolower((string) ($row->chargeback_status ?? ''));
        if (! in_array($status, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages([
                'chargeback_status' => ['Only pending or processing chargebacks can be contested.'],
            ]);
        }

        $noteText = trim((string) ($notes ?? ''));
        $combinedNotes = trim(($row->notes ?? '').($noteText !== '' ? ' | Contest: '.$noteText : ' | Merchant contested chargeback.'));

        DB::table('chargebacks')->where('id', $chargebackId)->update([
            'chargeback_status' => 'contested',
            'contested' => 'Yes',
            'notes' => $combinedNotes,
            'updated_at' => now(),
        ]);

        $this->notifyMerchant($merchant, $chargebackId, (string) $row->chargeback_request_id, (float) $row->chargeback_amount, 'INR', 'contested');

        return $this->formatChargebackRow($chargebackId, $merchant);
    }

    public function resolveTransaction(Merchant $merchant, string $reference): Transaction
    {
        if ($reference === '') {
            throw ValidationException::withMessages([
                'transaction_id' => ['Transaction ID is required.'],
            ]);
        }

        $query = Transaction::query()->where('merchant_id', $merchant->id);

        if (Schema::hasColumn('transactions', 'test_mode')) {
            $query->where('test_mode', (bool) $merchant->test_mode);
        }

        $transaction = (clone $query)
            ->where(function ($q) use ($reference) {
                $q->where('txn_id', $reference)
                    ->orWhere('gateway_txn_id', $reference);
                if (is_numeric($reference)) {
                    $q->orWhere('id', (int) $reference);
                }
            })
            ->first();

        if (! $transaction) {
            throw ValidationException::withMessages([
                'transaction_id' => ['No successful payment found for this reference in your current Test/Live mode.'],
            ]);
        }

        if (! in_array(strtolower((string) $transaction->status), $this->eligibleTxnStatuses, true)) {
            throw ValidationException::withMessages([
                'transaction_id' => ['Chargebacks can only be raised on captured/successful payments (status: '.$transaction->status.').'],
            ]);
        }

        return $transaction;
    }

    private function validateAmount(mixed $amount, Transaction $transaction): float
    {
        $amountFloat = filter_var(str_replace(',', '', (string) $amount), FILTER_VALIDATE_FLOAT);
        if ($amountFloat === false || (float) $amountFloat <= 0) {
            throw ValidationException::withMessages([
                'chargeback_amount' => ['A valid chargeback amount greater than zero is required.'],
            ]);
        }

        if ((float) $amountFloat > (float) $transaction->amount) {
            throw ValidationException::withMessages([
                'chargeback_amount' => ['Chargeback amount cannot exceed the original transaction amount ('.number_format((float) $transaction->amount, 2).').'],
            ]);
        }

        return (float) $amountFloat;
    }

    private function assertChargebackCapacity(Transaction $transaction, float $newAmount): void
    {
        $existing = (float) DB::table('chargebacks')
            ->where('transaction_id', $transaction->id)
            ->sum('chargeback_amount');

        if ($existing + $newAmount > (float) $transaction->amount + 0.001) {
            throw ValidationException::withMessages([
                'chargeback_amount' => [
                    'Total chargebacks for this payment would exceed the transaction amount. '
                    .'Remaining capacity: '.number_format(max(0, (float) $transaction->amount - $existing), 2).'.',
                ],
            ]);
        }
    }

    private function transactionHasRefund(int $transactionId): bool
    {
        if (! Schema::hasTable('refunds')) {
            return false;
        }

        return DB::table('refunds')
            ->where('transaction_id', $transactionId)
            ->whereIn('status', ['success', 'completed', 'processed'])
            ->exists();
    }

    public function generateRequestId(Merchant $merchant): string
    {
        return 'CB_'.$merchant->id.'_'.now()->format('YmdHis').'_'.strtoupper(Str::random(4));
    }

    public function normalizeStatus(string $raw): string
    {
        $v = strtolower(trim($raw));
        if ($v === 'disputed') {
            $v = 'contested';
        }

        $allowed = ['pending', 'contested', 'won', 'lost', 'processing'];

        return in_array($v, $allowed, true) ? $v : 'pending';
    }

    private function mapAcquirerDisputeStatus(string $acquirerStatus): string
    {
        $v = strtolower(trim($acquirerStatus));

        return match ($v) {
            'won', 'closed' => 'won',
            'lost' => 'lost',
            'under_review', 'open' => 'contested',
            default => 'pending',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function formatChargebackRow(int $id, Merchant $merchant): array
    {
        $row = DB::table('chargebacks')
            ->leftJoin('transactions', 'chargebacks.transaction_id', '=', 'transactions.id')
            ->where('chargebacks.id', $id)
            ->select('chargebacks.*', 'transactions.txn_id as transaction_txn_id', 'transactions.currency as txn_currency')
            ->first();

        return [
            'id' => $id,
            'chargeback_request_id' => $row->chargeback_request_id ?? '',
            'transaction_id' => $row->transaction_txn_id ?? '',
            'chargeback_amount' => number_format((float) ($row->chargeback_amount ?? 0), 2),
            'chargeback_status' => $row->chargeback_status ?? 'pending',
            'target_date' => $row->target_date ?? null,
            'contested' => $row->contested ?? 'No',
            'merchant_name' => $merchant->name,
        ];
    }

    private function notifyMerchant(Merchant $merchant, int $chargebackId, string $requestId, float $amount, string $currency, string $event): void
    {
        try {
            $messages = [
                'created' => sprintf('New chargeback %s — %s %s. Review and contest before the target date.', $requestId, $currency, number_format($amount, 2)),
                'updated' => sprintf('Chargeback %s updated by acquirer. Current status may require action.', $requestId),
                'contested' => sprintf('Chargeback %s marked as contested. Awaiting bank/acquirer decision.', $requestId),
            ];

            $msg = $messages[$event] ?? $messages['created'];
            $url = route('merchant.payments.chargebacks');

            $users = User::query()
                ->where('status', 'active')
                ->where('merchant_id', $merchant->id)
                ->whereHas('role', fn ($q) => $q->where('name', 'merchant'))
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
                        'type' => 'chargeback_'.$event,
                        'chargeback_id' => $chargebackId,
                        'chargeback_request_id' => $requestId,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Chargeback notification failed', ['error' => $e->getMessage()]);
        }
    }
}
