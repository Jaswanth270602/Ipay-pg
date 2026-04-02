<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('merchants') || ! Schema::hasColumn('merchants', 'merchant_type')) {
            return;
        }

        DB::table('merchants')
            ->where('merchant_type', 'vendor_merchant')
            ->update(['merchant_type' => 'merchant']);

        // Keep this MySQL-only to avoid cross-driver enum syntax issues.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `merchants` MODIFY `merchant_type` ENUM('merchant') NOT NULL DEFAULT 'merchant'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('merchants') || ! Schema::hasColumn('merchants', 'merchant_type')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `merchants` MODIFY `merchant_type` ENUM('merchant','vendor_merchant') NOT NULL DEFAULT 'merchant'");
        }
    }
};

