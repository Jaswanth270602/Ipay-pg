<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [
            RolesTableSeeder::class,
            BanksTableSeeder::class,
            MerchantsTableSeeder::class,
            UsersTableSeeder::class,
            ApiKeysSeeder::class,
            OrdersAndTransactionsSeeder::class,
            PaymentLinksSeeder::class,
            DisputesSeeder::class,
            WebhookEventTypesSeeder::class,
        ];

        // Vendor module was removed from schema; only seed demo vendors when table exists.
        if (Schema::hasTable('merchant_vendors')) {
            $seeders[] = DemoMerchantsWithVendorsSeeder::class;
        }

        $this->call($seeders);
    }
}

