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
            if (!Schema::hasColumn('bulk_refund_jobs', 'merchant_id')) {
                $table->unsignedBigInteger('merchant_id')->nullable()->after('user_id');
                $table->index('merchant_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bulk_refund_jobs')) {
            return;
        }

        Schema::table('bulk_refund_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_refund_jobs', 'merchant_id')) {
                $table->dropIndex(['merchant_id']);
                $table->dropColumn('merchant_id');
            }
        });
    }
};
