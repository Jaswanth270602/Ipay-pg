<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            BanksTableSeeder::class,
            MerchantsTableSeeder::class,
            UsersTableSeeder::class,
            ApiKeysSeeder::class,
            OrdersAndTransactionsSeeder::class,
            PaymentLinksSeeder::class,
            DisputesSeeder::class,
            WebhookEventTypesSeeder::class,
        ]);
    }
}

