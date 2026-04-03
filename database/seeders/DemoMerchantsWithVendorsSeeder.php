<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Merchant;
use App\Models\MerchantVendor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoMerchantsWithVendorsSeeder extends Seeder
{
    /**
     * Two demo merchants: first has 1 vendor, second has 2 vendors.
     * Login (test mode) — use admin panel in Test mode to see vendors.
     */
    public function run(): void
    {
        $merchantRole = Role::where('name', 'merchant')->firstOrFail();

        $merchantOne = Merchant::firstOrCreate(
            ['email' => 'demo.merchant.one@ipay.demo'],
            [
                'name' => 'Demo Merchant One',
                'company_name' => 'Demo Merchant One Pvt Ltd',
                'status' => 'active',
                'default_currency' => 'INR',
                'webhook_url' => 'https://demo-one.example.com/webhook',
                'webhook_secret' => Str::random(32),
                'test_mode' => true,
                'fee_percentage' => 2.50,
                'fee_flat' => 0.30,
                'business_details' => 'Seeded demo merchant (one vendor)',
                'settings' => ['auto_settle' => true],
            ]
        );

        $merchantTwo = Merchant::firstOrCreate(
            ['email' => 'demo.merchant.two@ipay.demo'],
            [
                'name' => 'Demo Merchant Two',
                'company_name' => 'Demo Merchant Two Pvt Ltd',
                'status' => 'active',
                'default_currency' => 'INR',
                'webhook_url' => 'https://demo-two.example.com/webhook',
                'webhook_secret' => Str::random(32),
                'test_mode' => true,
                'fee_percentage' => 2.00,
                'fee_flat' => 0.25,
                'business_details' => 'Seeded demo merchant (two vendors)',
                'settings' => ['auto_settle' => true],
            ]
        );

        User::firstOrCreate(
            ['email' => 'demo.merchant1@ipay.demo'],
            [
                'name' => 'Demo Merchant One User',
                'password' => Hash::make('Password123!'),
                'role_id' => $merchantRole->id,
                'merchant_id' => $merchantOne->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'demo.merchant2@ipay.demo'],
            [
                'name' => 'Demo Merchant Two User',
                'password' => Hash::make('Password123!'),
                'role_id' => $merchantRole->id,
                'merchant_id' => $merchantTwo->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        foreach ([$merchantOne, $merchantTwo] as $m) {
            if (! $m->apiKeys()->where('mode', 'test')->exists()) {
                ApiKey::generate($m->id, 'test', 'Demo seed — test');
            }
            if (! $m->apiKeys()->where('mode', 'live')->exists()) {
                ApiKey::generate($m->id, 'live', 'Demo seed — live');
            }
        }

        MerchantVendor::firstOrCreate(
            ['vendor_code' => 'VND_DEMO_M1_001'],
            [
                'merchant_id' => $merchantOne->id,
                'vendor_name' => 'Alpha Supplier Co',
                'vendor_email' => 'vendor.alpha1@ipay.demo',
                'vendor_phone' => '9876543210',
                'vendor_address' => 'Plot 100, Industrial Area, Bengaluru 560001',
                'vendor_pan_no' => 'ABCDE1234F',
                'vendor_login_id' => 'alpha_supplier_1',
                'bank_account_number' => '50100123456789',
                'bank_account_ifsc' => 'HDFC0001234',
                'bank_name' => 'HDFC Bank',
                'bank_branch' => 'MG Road',
                'bank_account_holder_name' => 'Alpha Supplier Co',
                'account_type' => 'Current Account',
                'upi_id' => 'alpha.supplier@okhdfcbank',
                'status' => 'approved',
                'note' => 'Seeded vendor for Demo Merchant One',
                'reference_id' => 'REF-M1-V1',
            ]
        );

        MerchantVendor::firstOrCreate(
            ['vendor_code' => 'VND_DEMO_M2_001'],
            [
                'merchant_id' => $merchantTwo->id,
                'vendor_name' => 'Beta Wholesale',
                'vendor_email' => 'vendor.beta1@ipay.demo',
                'vendor_phone' => '9123456780',
                'vendor_address' => 'Block A, Sector 18, Noida 201301',
                'vendor_pan_no' => 'FGHIJ5678K',
                'vendor_login_id' => 'beta_wholesale_1',
                'bank_account_number' => '30200299887766',
                'bank_account_ifsc' => 'SBIN0001234',
                'bank_name' => 'State Bank of India',
                'bank_branch' => 'Sector 18 Noida',
                'bank_account_holder_name' => 'Beta Wholesale',
                'account_type' => 'Current Account',
                'upi_id' => null,
                'status' => 'approved',
                'note' => 'Seeded vendor 1 for Demo Merchant Two',
                'reference_id' => 'REF-M2-V1',
            ]
        );

        MerchantVendor::firstOrCreate(
            ['vendor_code' => 'VND_DEMO_M2_002'],
            [
                'merchant_id' => $merchantTwo->id,
                'vendor_name' => 'Gamma Logistics',
                'vendor_email' => 'vendor.gamma2@ipay.demo',
                'vendor_phone' => '9988776655',
                'vendor_address' => 'Warehouse 7, MIDC, Pune 411019',
                'vendor_pan_no' => 'KLMNO9012P',
                'vendor_login_id' => 'gamma_logistics',
                'bank_account_number' => '77889900112233',
                'bank_account_ifsc' => 'ICIC0005678',
                'bank_name' => 'ICICI Bank',
                'bank_branch' => 'Hinjewadi',
                'bank_account_holder_name' => 'Gamma Logistics LLP',
                'account_type' => 'Current Account',
                'upi_id' => 'gamma.logistics@okicici',
                'status' => 'approved',
                'note' => 'Seeded vendor 2 for Demo Merchant Two',
                'reference_id' => 'REF-M2-V2',
            ]
        );

        // Default payment split: 20% to the first vendor for demo merchant one (new payments only).
        $vendorM1 = MerchantVendor::where('vendor_code', 'VND_DEMO_M1_001')->first();
        if ($merchantOne && $vendorM1) {
            $merchantOne->settings = array_merge($merchantOne->settings ?? [], [
                'split_merchant_vendor_id' => $vendorM1->id,
                'split_secondary_percentage' => 20,
            ]);
            $merchantOne->save();
        }

        $this->command->info('');
        $this->command->info('=== Demo merchants + vendors seeded ===');
        $this->command->info('Merchant One (1 vendor): demo.merchant1@ipay.demo / Password123!');
        $this->command->info('Merchant Two (2 vendors): demo.merchant2@ipay.demo / Password123!');
        $this->command->info('Admin: open Merchants → Merchant Vendors (Test mode) to see vendors.');
        $this->command->info('========================================');
    }
}
