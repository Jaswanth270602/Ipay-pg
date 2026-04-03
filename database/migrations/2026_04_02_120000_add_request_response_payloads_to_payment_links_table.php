<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_links', 'request_payload')) {
                $table->json('request_payload')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('payment_links', 'response_payload')) {
                $table->json('response_payload')->nullable()->after('request_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'response_payload')) {
                $table->dropColumn('response_payload');
            }
            if (Schema::hasColumn('payment_links', 'request_payload')) {
                $table->dropColumn('request_payload');
            }
        });
    }
};

