<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            if (! Schema::hasColumn('settlements', 'test_mode')) {
                $table->boolean('test_mode')->default(false)->after('merchant_id');
                $table->index(['merchant_id', 'test_mode']);
                $table->index('test_mode');
            }
        });

        Schema::table('settlement_details', function (Blueprint $table) {
            if (! Schema::hasColumn('settlement_details', 'test_mode')) {
                $table->boolean('test_mode')->default(false)->after('merchant_id');
                $table->index(['merchant_id', 'test_mode']);
            }
            if (! Schema::hasColumn('settlement_details', 'settlement_status')) {
                $table->string('settlement_status', 32)->default('pending')->after('acq_payment_id');
                $table->index('settlement_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            if (Schema::hasColumn('settlements', 'test_mode')) {
                $table->dropIndex(['merchant_id', 'test_mode']);
                $table->dropIndex(['test_mode']);
                $table->dropColumn('test_mode');
            }
        });

        Schema::table('settlement_details', function (Blueprint $table) {
            if (Schema::hasColumn('settlement_details', 'test_mode')) {
                $table->dropIndex(['merchant_id', 'test_mode']);
                $table->dropColumn('test_mode');
            }
            if (Schema::hasColumn('settlement_details', 'settlement_status')) {
                $table->dropIndex(['settlement_status']);
                $table->dropColumn('settlement_status');
            }
        });
    }
};
