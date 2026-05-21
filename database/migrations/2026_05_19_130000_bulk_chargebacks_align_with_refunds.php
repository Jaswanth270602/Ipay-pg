<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_chargeback_jobs')) {
            Schema::table('bulk_chargeback_jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('bulk_chargeback_jobs', 'merchant_id')) {
                    $table->unsignedBigInteger('merchant_id')->nullable()->after('user_id');
                    $table->index('merchant_id');
                }
                if (! Schema::hasColumn('bulk_chargeback_jobs', 'test_mode')) {
                    $table->boolean('test_mode')->nullable()->after('merchant_id');
                }
                if (! Schema::hasColumn('bulk_chargeback_jobs', 'status_info')) {
                    $table->text('status_info')->nullable()->after('error');
                }
            });

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE bulk_chargeback_jobs MODIFY COLUMN status ENUM('pending','processing','completed','completed_with_errors','failed') NOT NULL DEFAULT 'pending'");
            }
        }

        if (Schema::hasTable('chargebacks') && ! Schema::hasColumn('chargebacks', 'test_mode')) {
            Schema::table('chargebacks', function (Blueprint $table) {
                $table->boolean('test_mode')->default(false)->after('notes');
                $table->index(['merchant_id', 'test_mode']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bulk_chargeback_jobs') && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bulk_chargeback_jobs MODIFY COLUMN status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending'");
        }

        if (Schema::hasTable('bulk_chargeback_jobs')) {
            Schema::table('bulk_chargeback_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('bulk_chargeback_jobs', 'status_info')) {
                    $table->dropColumn('status_info');
                }
                if (Schema::hasColumn('bulk_chargeback_jobs', 'test_mode')) {
                    $table->dropColumn('test_mode');
                }
                if (Schema::hasColumn('bulk_chargeback_jobs', 'merchant_id')) {
                    $table->dropIndex(['merchant_id']);
                    $table->dropColumn('merchant_id');
                }
            });
        }

        if (Schema::hasTable('chargebacks') && Schema::hasColumn('chargebacks', 'test_mode')) {
            Schema::table('chargebacks', function (Blueprint $table) {
                $table->dropIndex(['merchant_id', 'test_mode']);
                $table->dropColumn('test_mode');
            });
        }
    }
};
