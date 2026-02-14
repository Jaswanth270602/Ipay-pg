<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dispute;
use App\Models\Merchant;
use App\Models\Transaction;

class DisputesSeeder extends Seeder
{
    public function run(): void
    {
        $merchantId = Merchant::value('id');
        if (!$merchantId) {
            // No merchants available, skip to avoid FK violations
            return;
        }

        $transactionIds = Transaction::limit(5)->pluck('id')->all();

        $payloads = [
            [ 'reason' => 'Chargeback - customer dispute', 'status' => 'action_required', 'amount' => 499.00, 'notes' => 'Customer claims no recognition' ],
            [ 'reason' => 'Duplicate charge', 'status' => 'under_review', 'amount' => 999.00, 'notes' => 'Provide invoice and logs' ],
            [ 'reason' => 'Product not delivered', 'status' => 'action_required', 'amount' => 1299.00, 'notes' => 'Courier issue' ],
            [ 'reason' => 'Fraudulent transaction', 'status' => 'insufficient_evidence', 'amount' => 2599.00, 'notes' => 'High-risk flagged' ],
            [ 'reason' => 'Incorrect amount charged', 'status' => 'action_required', 'amount' => 149.00, 'notes' => 'Billing mismatch' ],
        ];

        foreach ($payloads as $index => $data) {
            Dispute::create([
                'merchant_id' => $merchantId,
                'transaction_id' => $transactionIds[$index] ?? null,
                'reason' => $data['reason'],
                'status' => $data['status'],
                'amount' => $data['amount'],
                'currency' => 'INR',
                'internal_notes' => $data['notes'],
                'due_by' => now()->addDays(7),
                'evidence_submitted' => false,
                'dispute_fee' => 0,
                'frozen_amount' => $data['amount'],
            ]);
        }
    }
}


