<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_links') && Schema::hasColumn('payment_links', 'vendor_id')) {
            Schema::table('payment_links', function (Blueprint $table) {
                $table->dropColumn('vendor_id');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'vendor_id')) {
                    $table->dropColumn('vendor_id');
                }
                if (Schema::hasColumn('transactions', 'merchant_vendor_rate_snapshot_id')) {
                    $table->dropColumn('merchant_vendor_rate_snapshot_id');
                }
                if (Schema::hasColumn('transactions', 'merchant_vendor_split_percentage_snapshot')) {
                    $table->dropColumn('merchant_vendor_split_percentage_snapshot');
                }
            });
        }

        if (Schema::hasTable('refunds') && Schema::hasColumn('refunds', 'vendor_id')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->dropColumn('vendor_id');
            });
        }

        // Drop vendor-related tables (children first).
        Schema::dropIfExists('vendor_settlement_items');
        Schema::dropIfExists('vendor_settlements');
        Schema::dropIfExists('vendor_balances');
        Schema::dropIfExists('vendor_ledger_entries');
        Schema::dropIfExists('refund_split_allocations');
        Schema::dropIfExists('payment_splits');
        Schema::dropIfExists('merchant_vendor_rate_snapshots');
        Schema::dropIfExists('merchant_vendor_base_rates');
        Schema::dropIfExists('merchant_vendors');
    }

    public function down(): void
    {
        // Intentionally irreversible: vendor module removed from the platform.
    }
};

