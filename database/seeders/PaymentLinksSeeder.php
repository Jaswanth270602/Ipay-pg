<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\PaymentLink;
use Illuminate\Database\Seeder;

class PaymentLinksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchants = Merchant::all();
        $statuses = ['active', 'expired', 'paid', 'cancelled'];

        $linksCount = 0;

        foreach ($merchants as $merchant) {
            // Create 10-15 payment links per merchant
            $numLinks = rand(10, 15);

            for ($i = 0; $i < $numLinks; $i++) {
                $status = $statuses[array_rand($statuses)];
                $amount = rand(1000, 100000) / 100; // $10 to $1000

                $expiresAt = null;
                $paidAt = null;

                if ($status === 'expired') {
                    $expiresAt = now()->subDays(rand(1, 30));
                } elseif ($status === 'active') {
                    $expiresAt = now()->addDays(rand(1, 30));
                } elseif ($status === 'paid') {
                    $expiresAt = now()->addDays(rand(1, 30));
                    $paidAt = now()->subDays(rand(1, 15));
                }

                PaymentLink::create([
                    'merchant_id' => $merchant->id,
                    'link_token' => PaymentLink::generateLinkToken(),
                    'title' => 'Payment Link #' . ($linksCount + 1),
                    'description' => 'Test payment link for ' . $merchant->name,
                    'amount' => $amount,
                    'currency' => $merchant->default_currency,
                    'customer_details' => [
                        'name' => 'Customer ' . rand(1000, 9999),
                        'email' => 'customer' . rand(1000, 9999) . '@example.com',
                    ],
                    'status' => $status,
                    'usage_count' => $status === 'paid' ? 1 : 0,
                    'max_usage' => rand(1, 5),
                    'test_mode' => $merchant->test_mode,
                    'metadata' => [
                        'purpose' => 'invoice_payment',
                        'invoice_id' => 'INV_' . rand(10000, 99999),
                    ],
                    'success_url' => 'https://example.com/payment/success',
                    'cancel_url' => 'https://example.com/payment/cancel',
                    'expires_at' => $expiresAt,
                    'paid_at' => $paidAt,
                    'created_at' => now()->subDays(rand(1, 60)),
                    'updated_at' => now()->subDays(rand(0, 60)),
                ]);

                $linksCount++;
            }
        }

        $this->command->info("Seeded {$linksCount} payment links successfully.");
    }
}

