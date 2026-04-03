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
            // Only seed keys when the merchant has none — avoids stacking duplicate "dummy" keys on every db:seed
            if ($merchant->apiKeys()->exists()) {
                continue;
            }

            ApiKey::generate($merchant->id, 'test', 'Test API Key');
            ApiKey::generate($merchant->id, 'live', 'Live API Key');
        }

        $this->command->info('API keys seeded successfully.');
        $this->command->info('Check api_keys table for generated keys and secrets.');
    }
}

