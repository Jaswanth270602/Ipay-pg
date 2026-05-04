<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_refund_jobs')) {
            return;
        }

        Schema::table('bulk_refund_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('bulk_refund_jobs', 'test_mode')) {
                $table->boolean('test_mode')->nullable()->after('merchant_id');
                $table->index('test_mode');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bulk_refund_jobs')) {
            return;
        }

        Schema::table('bulk_refund_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_refund_jobs', 'test_mode')) {
                $table->dropIndex(['test_mode']);
                $table->dropColumn('test_mode');
            }
        });
    }
};
