<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dispute;
use App\Models\Merchant;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SeedTestDisputes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disputes:seed-test {merchant_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample disputes for testing (Razorpay-style)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $merchantId = $this->argument('merchant_id');
        
        if ($merchantId) {
            $merchant = Merchant::find($merchantId);
            if (!$merchant) {
                $this->error("Merchant with ID {$merchantId} not found.");
                return 1;
            }
        } else {
            $merchant = Merchant::first();
            if (!$merchant) {
                $this->error("No merchants found. Please create a merchant first.");
                return 1;
            }
        }

        $this->info("Creating sample disputes for merchant: {$merchant->name} (ID: {$merchant->id})");

        // Get some transactions for reference (optional)
        $transactions = Transaction::where('merchant_id', $merchant->id)->limit(5)->get();

        // Sample dispute data with Razorpay-style statuses and reasons
        $disputes = [
            [
                'merchant_id' => $merchant->id,
                'payment_id' => null, // Will be set to transaction txn_id if available
                'order_id' => 'order_' . Str::random(14),
                'transaction_id' => $transactions->isNotEmpty() ? $transactions->first()->id : null,
                'card_network' => 'VISA',
                'reason' => 'fraud',
                'status' => 'action_required',
                'amount' => 2500.00,
                'currency' => 'INR',
                'due_by' => Carbon::now()->addDays(7),
                'evidence_submitted' => false,
                'frozen_amount' => 2500.00,
                'dispute_fee' => 0,
            ],
            [
                'merchant_id' => $merchant->id,
                'payment_id' => null, // payment_id is integer in DB
                'order_id' => 'order_' . Str::random(14),
                'transaction_id' => $transactions->count() > 1 ? $transactions->skip(1)->first()->id : null,
                'card_network' => 'MASTERCARD',
                'reason' => 'product_not_received',
                'status' => 'action_required',
                'amount' => 1500.00,
                'currency' => 'INR',
                'due_by' => Carbon::today()->addDays(1), // Due tomorrow
                'evidence_submitted' => false,
                'frozen_amount' => 1500.00,
                'dispute_fee' => 0,
            ],
            [
                'merchant_id' => $merchant->id,
                'payment_id' => null, // payment_id is integer in DB, set to null
                'order_id' => 'order_' . Str::random(14),
                'transaction_id' => $transactions->count() > 2 ? $transactions->skip(2)->first()->id : null,
                'card_network' => 'RUPAY',
                'reason' => 'product_not_as_described',
                'status' => 'under_review',
                'amount' => 3500.00,
                'currency' => 'INR',
                'due_by' => Carbon::now()->subDays(2), // Past due
                'evidence_submitted' => true,
                'frozen_amount' => 3500.00,
                'dispute_fee' => 0,
            ],
            [
                'merchant_id' => $merchant->id,
                'payment_id' => null, // payment_id is integer in DB, set to null
                'order_id' => 'order_' . Str::random(14),
                'transaction_id' => $transactions->count() > 3 ? $transactions->skip(3)->first()->id : null,
                'card_network' => 'VISA',
                'reason' => 'duplicate_charge',
                'status' => 'insufficient_evidence',
                'amount' => 899.00,
                'currency' => 'INR',
                'due_by' => Carbon::now()->subDays(10),
                'evidence_submitted' => false,
                'frozen_amount' => 899.00,
                'dispute_fee' => 0,
            ],
            [
                'merchant_id' => $merchant->id,
                'payment_id' => null, // payment_id is integer in DB, set to null
                'order_id' => 'order_' . Str::random(14),
                'transaction_id' => $transactions->count() > 4 ? $transactions->skip(4)->first()->id : null,
                'card_network' => 'MASTERCARD',
                'reason' => 'refund_not_processed',
                'status' => 'won',
                'amount' => 2000.00,
                'currency' => 'INR',
                'due_by' => Carbon::now()->subDays(30),
                'evidence_submitted' => true,
                'frozen_amount' => 0, // Released after win
                'dispute_fee' => 0,
            ],
            [
                'merchant_id' => $merchant->id,
                'payment_id' => null, // payment_id is integer in DB
                'order_id' => 'order_' . Str::random(14),
                'card_network' => 'VISA',
                'reason' => 'fraud',
                'status' => 'lost',
                'amount' => 5000.00,
                'currency' => 'INR',
                'due_by' => Carbon::now()->subDays(45),
                'evidence_submitted' => true,
                'frozen_amount' => 0,
                'dispute_fee' => 100.00, // 2% fee charged
            ],
            [
                'merchant_id' => $merchant->id,
                'payment_id' => null,
                'order_id' => 'order_' . Str::random(14),
                'card_network' => 'RUPAY',
                'reason' => 'subscription_canceled',
                'status' => 'closed',
                'amount' => 999.00,
                'currency' => 'INR',
                'due_by' => Carbon::now()->subDays(60),
                'evidence_submitted' => true,
                'frozen_amount' => 0,
                'dispute_fee' => 0,
            ],
        ];

        $created = 0;
        foreach ($disputes as $disputeData) {
            try {
                $dispute = Dispute::create($disputeData);
                $this->info("✓ Created dispute: {$dispute->id} - Status: {$dispute->status} - Amount: ₹{$dispute->amount}");
                $created++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to create dispute: " . $e->getMessage());
            }
        }

        $this->info("\n✅ Successfully created {$created} sample disputes!");
        $this->info("You can now view them in the merchant dashboard at: /merchant/disputes");

        return 0;
    }
}

