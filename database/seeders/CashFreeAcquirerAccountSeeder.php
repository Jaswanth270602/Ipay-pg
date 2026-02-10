<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcquirerAccount;
use Illuminate\Support\Facades\DB;

class CashFreeAcquirerAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CashFree App ID and Secret Key
       $appId = env('CASHFREE_API_KEY');
        $secretKey = env('CASHFREE_API_SECRET');

        // Check if CashFree account already exists
        $cashfreeAccount = AcquirerAccount::where('acquirer_name', 'cashfree')
            ->orWhere('acquirer_name', 'CashFree')
            ->orWhere('acquirer_name', 'Cashfree')
            ->first();

        if ($cashfreeAccount) {
            // Update existing CashFree account
            $cashfreeAccount->update([
                'account_id' => $appId, // App ID goes in account_id
                'additional_key_1' => $appId, // Also store in additional_key_1 as fallback
                'secret_key' => $secretKey,
                'mode' => 'TEST', // Since it's a TEST App ID
                'test_request_url' => 'https://sandbox.cashfree.com/pg',
                'test_query_url' => 'https://sandbox.cashfree.com/pg',
                'test_refund_url' => 'https://sandbox.cashfree.com/pg',
                'live_request_url' => 'https://api.cashfree.com/pg',
                'live_query_url' => 'https://api.cashfree.com/pg',
                'live_refund_url' => 'https://api.cashfree.com/pg',
                'is_active' => true,
                'refund_allowed' => true,
                'settlements_to_be_created' => true,
            ]);

            $this->command->info('CashFree acquirer account updated successfully!');
            $this->command->info('Account ID: ' . $cashfreeAccount->id);
        } else {
            // Create new CashFree account
            $cashfreeAccount = AcquirerAccount::create([
                'account_id' => $appId, // App ID goes in account_id
                'acquirer_name' => 'cashfree',
                'additional_key_1' => $appId, // Also store in additional_key_1 as fallback
                'secret_key' => $secretKey,
                'mode' => 'TEST', // Since it's a TEST App ID
                'test_request_url' => 'https://sandbox.cashfree.com/pg',
                'test_query_url' => 'https://sandbox.cashfree.com/pg',
                'test_refund_url' => 'https://sandbox.cashfree.com/pg',
                'live_request_url' => 'https://api.cashfree.com/pg',
                'live_query_url' => 'https://api.cashfree.com/pg',
                'live_refund_url' => 'https://api.cashfree.com/pg',
                'is_active' => true,
                'refund_allowed' => true,
                'settlements_to_be_created' => true,
                'description' => 'CashFree Payment Gateway Account',
            ]);

            $this->command->info('CashFree acquirer account created successfully!');
            $this->command->info('Account ID: ' . $cashfreeAccount->id);
        }

        $this->command->info('CashFree credentials configured:');
        $this->command->info('  App ID: ' . $appId);
        $this->command->info('  Secret Key: ' . substr($secretKey, 0, 20) . '...');
        $this->command->info('  Mode: TEST');
        $this->command->info('  Base URL (Test): https://sandbox.cashfree.com/pg');
    }
}

