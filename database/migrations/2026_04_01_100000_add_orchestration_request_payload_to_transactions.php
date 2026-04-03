<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'request_payload')) {
                $table->json('request_payload')->nullable()->after('gateway_response');
            }
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->index('secret');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'request_payload')) {
                $table->dropColumn('request_payload');
            }
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex(['secret']);
        });
    }
};
