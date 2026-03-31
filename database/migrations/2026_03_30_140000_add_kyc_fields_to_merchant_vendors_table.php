<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('merchant_vendors', 'kyc_verified')) {
                $table->boolean('kyc_verified')->default(false)->after('status');
            }
            if (!Schema::hasColumn('merchant_vendors', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable()->after('kyc_verified');
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchant_vendors', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_vendors', 'kyc_verified_at')) {
                $table->dropColumn('kyc_verified_at');
            }
            if (Schema::hasColumn('merchant_vendors', 'kyc_verified')) {
                $table->dropColumn('kyc_verified');
            }
        });
    }
};

