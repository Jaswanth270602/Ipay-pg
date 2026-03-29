<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add gateway mode (test/live) and refund workflow statuses.
     */
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'mode')) {
                $table->string('mode', 10)->default('test')->after('currency');
            }
        });

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (Schema::hasColumn('refunds', 'mode')) {
                $table->dropColumn('mode');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE refunds MODIFY COLUMN status ENUM(
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'");
        }
    }
};
