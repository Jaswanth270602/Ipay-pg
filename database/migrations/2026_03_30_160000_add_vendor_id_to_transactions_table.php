<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')
                    ->nullable()
                    ->after('merchant_id');
                $table->index('vendor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'vendor_id')) {
                $table->dropIndex(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};

