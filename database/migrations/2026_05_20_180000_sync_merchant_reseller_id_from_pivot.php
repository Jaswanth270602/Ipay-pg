<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill merchants.reseller_id from reseller_merchant when the column was never set.
     */
    public function up(): void
    {
        if (! Schema::hasTable('reseller_merchant') || ! Schema::hasColumn('merchants', 'reseller_id')) {
            return;
        }

        DB::table('reseller_merchant')
            ->select('merchant_id', 'reseller_id')
            ->orderBy('merchant_id')
            ->each(function ($row) {
                DB::table('merchants')
                    ->where('id', $row->merchant_id)
                    ->whereNull('reseller_id')
                    ->update(['reseller_id' => $row->reseller_id]);
            });
    }

    public function down(): void
    {
        // Data repair only; no rollback.
    }
};
