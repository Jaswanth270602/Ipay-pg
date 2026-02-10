<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Merchant;
use Illuminate\Database\Seeder;

class ApiKeysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchants = Merchant::all();

        foreach ($merchants as $merchant) {
            // Always create test mode API key (for testing)
            ApiKey::generate($merchant->id, 'test', 'Test API Key');

            // Always create live mode API key (merchants can switch modes)
            // Note: Live keys should only be used in production
            ApiKey::generate($merchant->id, 'live', 'Live API Key');
        }

        $this->command->info('API keys seeded successfully.');
        $this->command->info('Check api_keys table for generated keys and secrets.');
    }
}

