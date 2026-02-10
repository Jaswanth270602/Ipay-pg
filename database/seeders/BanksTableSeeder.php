<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BanksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'name' => 'HDFC Bank',
                'code' => 'HDFC',
                'sandbox_endpoint' => 'https://sandbox.hdfc.com/api',
                'production_endpoint' => 'https://api.hdfc.com',
                'is_active' => true,
                'supported_methods' => ['card', 'netbanking', 'upi'],
            ],
            [
                'name' => 'ICICI Bank',
                'code' => 'ICICI',
                'sandbox_endpoint' => 'https://sandbox.icici.com/api',
                'production_endpoint' => 'https://api.icici.com',
                'is_active' => true,
                'supported_methods' => ['card', 'netbanking', 'upi'],
            ],
            [
                'name' => 'State Bank of India',
                'code' => 'SBI',
                'sandbox_endpoint' => 'https://sandbox.sbi.com/api',
                'production_endpoint' => 'https://api.sbi.com',
                'is_active' => true,
                'supported_methods' => ['card', 'netbanking', 'upi'],
            ],
            [
                'name' => 'Axis Bank',
                'code' => 'AXIS',
                'sandbox_endpoint' => 'https://sandbox.axisbank.com/api',
                'production_endpoint' => 'https://api.axisbank.com',
                'is_active' => true,
                'supported_methods' => ['card', 'netbanking', 'upi', 'wallet'],
            ],
        ];

        foreach ($banks as $bank) {
            Bank::firstOrCreate(
                ['code' => $bank['code']],
                $bank
            );
        }

        $this->command->info('Banks seeded successfully.');
    }
}

