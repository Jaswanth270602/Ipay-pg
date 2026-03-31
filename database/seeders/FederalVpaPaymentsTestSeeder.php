<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FederalVpaPaymentsTestSeeder extends Seeder
{
    /**
     * Inserts sample rows into federal_vpa_payments for local / QA testing.
     * Run: php artisan db:seed --class=FederalVpaPaymentsTestSeeder
     */
    public function run(): void
    {
        if (! Schema::hasTable('federal_vpa_payments')) {
            $this->command?->warn('federal_vpa_payments table missing; run migrations first.');

            return;
        }

        $merchantId = Merchant::query()->orderBy('id')->value('id');
        if (! $merchantId) {
            $this->command?->warn('FederalVpaPaymentsTestSeeder: no merchants found, skipping.');

            return;
        }

        // Idempotent: avoid duplicate TEST-* rows if seeder runs multiple times
        if (Schema::hasColumn('federal_vpa_payments', 'reference_id')) {
            DB::table('federal_vpa_payments')
                ->where('merchant_id', $merchantId)
                ->whereIn('reference_id', ['TEST-REF-VPA-001', 'TEST-REF-VPA-002'])
                ->delete();
        }

        $now = now();
        $hasResponseCols = Schema::hasColumn('federal_vpa_payments', 'response_received');

        $base = [
            'merchant_id' => $merchantId,
            'statement_date' => $now->toDateString(),
            'vpa_id' => 'merchant.test@federal',
            'currency' => 'INR',
            'transaction_type' => 'credit',
            'value_date' => $now->toDateString(),
            'file_path' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $rows = [
            array_merge($base, [
                'reference_id' => 'TEST-REF-VPA-001',
                'statement_id' => 'STMT_TEST_'.strtoupper(bin2hex(random_bytes(6))),
                'transaction_id' => 'TXN_FED_'.strtoupper(bin2hex(random_bytes(4))),
                'order_id' => null,
                'amount' => 1500.00,
                'reference_number' => 'REF-001',
                'utr_number' => '123456789012',
                'description' => 'Federal Direct VPA test — processed (sample)',
                'status' => 'processed',
                'response_received' => true,
                'response_data' => json_encode(['source' => 'FederalVpaPaymentsTestSeeder', 'note' => 'Sample processed row']),
            ]),
            array_merge($base, [
                'reference_id' => 'TEST-REF-VPA-002',
                'statement_id' => 'STMT_TEST_'.strtoupper(bin2hex(random_bytes(6))),
                'transaction_id' => 'TXN_FED_'.strtoupper(bin2hex(random_bytes(4))),
                'order_id' => null,
                'amount' => 99.50,
                'reference_number' => 'REF-002',
                'utr_number' => null,
                'description' => 'Federal Direct VPA test — pending (sample)',
                'status' => 'pending',
                'response_received' => false,
                'response_data' => null,
            ]),
        ];

        foreach ($rows as $row) {
            if (! $hasResponseCols) {
                unset($row['response_received'], $row['response_data']);
            }
            if (! Schema::hasColumn('federal_vpa_payments', 'reference_id')) {
                unset($row['reference_id']);
            }
            DB::table('federal_vpa_payments')->insert($row);
        }

        $this->command?->info('FederalVpaPaymentsTestSeeder: inserted '.count($rows).' test row(s) for merchant_id '.$merchantId.'.');
    }
}
