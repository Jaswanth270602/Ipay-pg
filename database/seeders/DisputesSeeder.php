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
            [ 'reason' => 'Chargeback - customer dispute', 'status' => 'open', 'amount' => 499.00, 'notes' => 'Customer claims no recognition' ],
            [ 'reason' => 'Duplicate charge', 'status' => 'needs_evidence', 'amount' => 999.00, 'notes' => 'Provide invoice and logs' ],
            [ 'reason' => 'Product not delivered', 'status' => 'open', 'amount' => 1299.00, 'notes' => 'Courier issue' ],
            [ 'reason' => 'Fraudulent transaction', 'status' => 'open', 'amount' => 2599.00, 'notes' => 'High-risk flagged' ],
            [ 'reason' => 'Incorrect amount charged', 'status' => 'open', 'amount' => 149.00, 'notes' => 'Billing mismatch' ],
        ];

        foreach ($payloads as $index => $data) {
            Dispute::create([
                'merchant_id' => $merchantId,
                'transaction_id' => $transactionIds[$index] ?? null,
                'reason' => $data['reason'],
                'status' => $data['status'],
                'amount' => $data['amount'],
                'evidence_url' => null,
                'notes' => $data['notes'],
            ]);
        }
    }
}


