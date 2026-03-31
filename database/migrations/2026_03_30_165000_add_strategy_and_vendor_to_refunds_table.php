<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'mode')) {
                $table->string('mode', 10)->default('test')->after('currency');
            }

            if (! Schema::hasColumn('refunds', 'refund_strategy')) {
                $table->string('refund_strategy', 32)
                    ->default('proportional')
                    ->after('mode');
            }

            if (! Schema::hasColumn('refunds', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')
                    ->nullable()
                    ->after('merchant_id');
                $table->index('vendor_id');
            }
        });

        // Ensure enum includes new statuses when using MySQL
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE refunds MODIFY COLUMN status ENUM(
                'pending',
                'pending_approval',
                'pending_processing',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (Schema::hasColumn('refunds', 'vendor_id')) {
                $table->dropIndex(['vendor_id']);
                $table->dropColumn('vendor_id');
            }

            if (Schema::hasColumn('refunds', 'refund_strategy')) {
                $table->dropColumn('refund_strategy');
            }

            if (Schema::hasColumn('refunds', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }
};

