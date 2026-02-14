<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $merchantRole = Role::where('name', 'merchant')->first();
        $userRole = Role::where('name', 'user')->first();

        $merchantA = Merchant::where('email', 'merchant.a@ipay.test')->first();
        $merchantB = Merchant::where('email', 'merchant.b@ipay.test')->first();

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@ipay.test',
                'password' => Hash::make('Password123!'),
                'role_id' => $adminRole->id,
                'merchant_id' => null,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Merchant A User',
                'email' => 'merchant1@ipay.test',
                'password' => Hash::make('Password123!'),
                'role_id' => $merchantRole->id,
                'merchant_id' => $merchantA->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Merchant B User',
                'email' => 'merchant2@ipay.test',
                'password' => Hash::make('Password123!'),
                'role_id' => $merchantRole->id,
                'merchant_id' => $merchantB->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Users seeded successfully.');
        $this->command->info('');
        $this->command->info('=== Test User Credentials ===');
        $this->command->info('Admin: admin@ipay.test / Password123!');
        $this->command->info('Merchant 1: merchant1@ipay.test / Password123!');
        $this->command->info('Merchant 2: merchant2@ipay.test / Password123!');
        $this->command->info('============================');
    }
}

