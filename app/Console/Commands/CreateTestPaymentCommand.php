<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\Merchant;
use App\Models\PaymentLink;
use Illuminate\Console\Command;

/**
 * Create a test payment link and output the customer checkout URL.
 * Use this to quickly open the payment page and complete a successful test payment.
 */
class CreateTestPaymentCommand extends Command
{
    protected $signature = 'payment:create-test
                            {--amount=99.99 : Amount to pay}
                            {--api-key= : API key (or set TEST_MERCHANT_API_KEY in .env)}';

    protected $description = 'Create a test payment and print the checkout URL for customer payment';

    public function handle(): int
    {
        $apiKeyValue = $this->option('api-key') ?: env('TEST_MERCHANT_API_KEY');

        if (!$apiKeyValue) {
            $key = ApiKey::where('mode', 'test')
                ->where('status', 'active')
                ->first();
            if (!$key) {
                $this->error('No test API key found. Run: php artisan db:seed (ApiKeysSeeder) or set TEST_MERCHANT_API_KEY in .env');
                return self::FAILURE;
            }
            $apiKeyValue = $key->key;
            $this->warn("Using first available test API key. For a specific merchant set TEST_MERCHANT_API_KEY in .env.");
        }

        $apiKey = ApiKey::where('key', $apiKeyValue)->first();
        if (!$apiKey || !$apiKey->isValid()) {
            $this->error('Invalid or inactive API key.');
            return self::FAILURE;
        }

        $merchant = $apiKey->merchant;
        if (!$merchant) {
            $this->error('Merchant not found for this API key.');
            return self::FAILURE;
        }

        $amount = (float) $this->option('amount');
        if ($amount < 0.01) {
            $this->error('Amount must be at least 0.01');
            return self::FAILURE;
        }

        $acquirerAccount = $merchant->getActiveAcquirerAccount();
        if (!$acquirerAccount) {
            $this->warn('No active acquirer account (Razorpay/Cashfree) for this merchant. Payment page will use simulation mode.');
            $this->warn('To get real Razorpay test payment: Admin → Acquirer Accounts → attach Razorpay TEST account to this merchant.');
        }

        $paymentLink = PaymentLink::create([
            'merchant_id' => $merchant->id,
            'link_token' => PaymentLink::generateLinkToken(),
            'title' => 'Test Payment',
            'description' => 'Customer test payment',
            'amount' => $amount,
            'currency' => $merchant->default_currency ?? 'INR',
            'allow_partial_payment' => false,
            'amount_paid' => 0,
            'status' => 'active',
            'test_mode' => $merchant->test_mode,
            'payment_methods' => ['card', 'upi', 'netbanking', 'wallet'],
            'expires_at' => now()->addHours(24),
            'usage_count' => 0,
            'metadata' => ['source' => 'payment:create-test'],
        ]);

        $checkoutUrl = url('/pay/' . $paymentLink->link_token);

        $this->newLine();
        $this->info('Test payment created.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Link token', $paymentLink->link_token],
                ['Amount', $paymentLink->amount . ' ' . $paymentLink->currency],
                ['Merchant', $merchant->name ?? $merchant->company_name ?? $merchant->id],
            ]
        );
        $this->newLine();
        $this->line('  <fg=green>Checkout URL (open in browser to pay as customer):</>');
        $this->line('  ' . $checkoutUrl);
        $this->line('  (If you use <fg=cyan>php artisan serve</>, set APP_URL=http://127.0.0.1:8000 in .env so this URL works.)');
        $this->newLine();
        $this->line('  For <fg=cyan>successful</> Razorpay test payment use card: <fg=yellow>4111 1111 1111 1111</>');
        $this->line('  CVV: any 3 digits, Expiry: any future date.');
        $this->newLine();

        return self::SUCCESS;
    }
}
