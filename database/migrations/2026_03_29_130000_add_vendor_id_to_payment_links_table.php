<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_links', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('merchant_id');
                $table->index('vendor_id', 'payment_links_vendor_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'vendor_id')) {
                $table->dropIndex('payment_links_vendor_id_index');
                $table->dropColumn('vendor_id');
            }
        });
    }
};

