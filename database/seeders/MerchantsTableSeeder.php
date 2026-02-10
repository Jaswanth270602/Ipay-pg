<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MerchantsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchants = [
            [
                'name' => 'Test Merchant A',
                'email' => 'merchant.a@badlicash.test',
                'company_name' => 'Acme Corp',
                'status' => 'active',
                'default_currency' => 'INR',
                'webhook_url' => 'https://acme.com/webhooks/badlicash',
                'webhook_secret' => Str::random(32),
                'test_mode' => true,
                'fee_percentage' => 2.50,
                'fee_flat' => 0.30,
                'business_details' => 'E-commerce company selling various products',
                'settings' => [
                    'auto_settle' => true,
                    'settlement_frequency' => 'daily',
                ],
            ],
            [
                'name' => 'Test Merchant B',
                'email' => 'merchant.b@badlicash.test',
                'company_name' => 'Beta Services Inc',
                'status' => 'active',
                'default_currency' => 'INR',
                'webhook_url' => 'https://beta-services.com/webhooks/payment',
                'webhook_secret' => Str::random(32),
                'test_mode' => true,
                'fee_percentage' => 2.00,
                'fee_flat' => 0.25,
                'business_details' => 'SaaS platform for business management',
                'settings' => [
                    'auto_settle' => false,
                    'settlement_frequency' => 'weekly',
                ],
            ],
            [
                'name' => 'Live Merchant Demo',
                'email' => 'live.merchant@badlicash.test',
                'company_name' => 'Live Commerce Ltd',
                'status' => 'active',
                'default_currency' => 'INR',
                'webhook_url' => 'https://livecommerce.com/webhooks/payments',
                'webhook_secret' => Str::random(32),
                'test_mode' => false,
                'fee_percentage' => 1.80,
                'fee_flat' => 0.20,
                'business_details' => 'Production merchant for live transactions',
                'settings' => [
                    'auto_settle' => true,
                    'settlement_frequency' => 'daily',
                ],
            ],
        ];

        foreach ($merchants as $merchantData) {
            Merchant::firstOrCreate(
                ['email' => $merchantData['email']],
                $merchantData
            );
        }

        $this->command->info('Merchants seeded successfully.');
    }
}

