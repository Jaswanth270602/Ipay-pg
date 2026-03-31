<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_refund_jobs')) {
            Schema::table('bulk_refund_jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('bulk_refund_jobs', 'row_results_json')) {
                    $table->longText('row_results_json')->nullable()->after('status_info');
                }
            });
        }

        if (Schema::hasTable('acquirer_account_upload_jobs')) {
            Schema::table('acquirer_account_upload_jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('acquirer_account_upload_jobs', 'row_results_json')) {
                    $table->longText('row_results_json')->nullable()->after('status_info');
                }
            });
        }

        if (Schema::hasTable('bulk_chargeback_jobs')) {
            Schema::table('bulk_chargeback_jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('bulk_chargeback_jobs', 'row_results_json')) {
                    $table->longText('row_results_json')->nullable()->after('error');
                }
            });
        }

        // Support partial-success state for bulk refund jobs.
        if (Schema::hasTable('bulk_refund_jobs') && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bulk_refund_jobs MODIFY COLUMN status ENUM('pending','processing','completed','completed_with_errors','failed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bulk_refund_jobs')) {
            Schema::table('bulk_refund_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('bulk_refund_jobs', 'row_results_json')) {
                    $table->dropColumn('row_results_json');
                }
            });
        }

        if (Schema::hasTable('acquirer_account_upload_jobs')) {
            Schema::table('acquirer_account_upload_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('acquirer_account_upload_jobs', 'row_results_json')) {
                    $table->dropColumn('row_results_json');
                }
            });
        }

        if (Schema::hasTable('bulk_chargeback_jobs')) {
            Schema::table('bulk_chargeback_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('bulk_chargeback_jobs', 'row_results_json')) {
                    $table->dropColumn('row_results_json');
                }
            });
        }

        if (Schema::hasTable('bulk_refund_jobs') && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bulk_refund_jobs MODIFY COLUMN status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending'");
        }
    }
};

