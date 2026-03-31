<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_rate_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('base_rate_id')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('service_type', 32)->default('payment');
            $table->string('transaction_type', 32)->default('domestic');
            $table->decimal('percentage_fee', 8, 4)->default(0);
            $table->decimal('flat_fee', 14, 4)->default(0);
            $table->decimal('gst_percentage', 8, 4)->default(18);
            $table->decimal('effective_fee_percentage', 8, 4)->default(0);
            $table->timestamps();
            $table->index(['merchant_id', 'created_at']);
        });

        Schema::create('merchant_vendor_base_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('vendor_id');
            $table->string('payment_method', 32)->nullable()->comment('NULL=all methods');
            $table->string('service_type', 32)->default('payment');
            $table->string('currency', 8)->nullable()->default('INR');
            $table->decimal('percentage_share', 8, 4)->default(0);
            $table->decimal('flat_share', 14, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['merchant_id', 'vendor_id', 'payment_method', 'service_type', 'is_active'], 'mvr_lookup_idx');
        });

        Schema::create('merchant_vendor_rate_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('merchant_vendor_base_rate_id')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('service_type', 32)->default('payment');
            $table->decimal('percentage_share', 8, 4)->default(0);
            $table->decimal('flat_share', 14, 4)->default(0);
            $table->decimal('effective_split_percentage', 8, 4)->default(0);
            $table->timestamps();
            $table->index(['merchant_id', 'vendor_id', 'created_at'], 'mvrs_lookup_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'admin_rate_snapshot_id')) {
                $table->unsignedBigInteger('admin_rate_snapshot_id')->nullable()->after('vendor_id');
                $table->decimal('admin_fee_percentage_snapshot', 8, 4)->nullable()->after('admin_rate_snapshot_id');
                $table->unsignedBigInteger('merchant_vendor_rate_snapshot_id')->nullable()->after('admin_fee_percentage_snapshot');
                $table->decimal('merchant_vendor_split_percentage_snapshot', 8, 4)->nullable()->after('merchant_vendor_rate_snapshot_id');
                $table->index('admin_rate_snapshot_id');
                $table->index('merchant_vendor_rate_snapshot_id');
            }
        });

        Schema::table('payment_splits', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_splits', 'admin_rate_snapshot_id')) {
                $table->unsignedBigInteger('admin_rate_snapshot_id')->nullable()->after('merchant_id');
                $table->unsignedBigInteger('merchant_vendor_rate_snapshot_id')->nullable()->after('admin_rate_snapshot_id');
                $table->decimal('merchant_vendor_split_percentage_snapshot', 8, 4)->nullable()->after('merchant_vendor_rate_snapshot_id');
                $table->index('admin_rate_snapshot_id');
                $table->index('merchant_vendor_rate_snapshot_id');
            }
        });

        Schema::table('refund_split_allocations', function (Blueprint $table) {
            if (! Schema::hasColumn('refund_split_allocations', 'admin_rate_snapshot_id')) {
                $table->unsignedBigInteger('admin_rate_snapshot_id')->nullable()->after('payment_split_id');
                $table->unsignedBigInteger('merchant_vendor_rate_snapshot_id')->nullable()->after('admin_rate_snapshot_id');
                $table->decimal('merchant_vendor_split_percentage_snapshot', 8, 4)->nullable()->after('merchant_vendor_rate_snapshot_id');
                $table->index('admin_rate_snapshot_id');
                $table->index('merchant_vendor_rate_snapshot_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('refund_split_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('refund_split_allocations', 'merchant_vendor_split_percentage_snapshot')) {
                $table->dropColumn([
                    'admin_rate_snapshot_id',
                    'merchant_vendor_rate_snapshot_id',
                    'merchant_vendor_split_percentage_snapshot',
                ]);
            }
        });

        Schema::table('payment_splits', function (Blueprint $table) {
            if (Schema::hasColumn('payment_splits', 'merchant_vendor_split_percentage_snapshot')) {
                $table->dropColumn([
                    'admin_rate_snapshot_id',
                    'merchant_vendor_rate_snapshot_id',
                    'merchant_vendor_split_percentage_snapshot',
                ]);
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'merchant_vendor_split_percentage_snapshot')) {
                $table->dropColumn([
                    'admin_rate_snapshot_id',
                    'admin_fee_percentage_snapshot',
                    'merchant_vendor_rate_snapshot_id',
                    'merchant_vendor_split_percentage_snapshot',
                ]);
            }
        });

        Schema::dropIfExists('merchant_vendor_rate_snapshots');
        Schema::dropIfExists('merchant_vendor_base_rates');
        Schema::dropIfExists('merchant_rate_snapshots');
    }
};

