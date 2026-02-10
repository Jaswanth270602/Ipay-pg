<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Settlement tracking
            $table->foreignId('settlement_id')->nullable()->after('net_amount')->constrained('settlements')->onDelete('set null');
            $table->enum('settlement_status', ['pending', 'settled', 'on_hold', 'excluded'])->default('pending')->after('status');
            $table->timestamp('settled_at')->nullable()->after('captured_at');
            
            // GST and additional fees
            $table->decimal('gst_amount', 10, 2)->default(0)->after('fee_amount')->comment('GST on commission (18%)');
            $table->decimal('other_fees', 10, 2)->default(0)->after('gst_amount')->comment('Other fees if any');
            
            // Indexes for settlement queries
            $table->index('settlement_status');
            $table->index(['merchant_id', 'settlement_status']);
            $table->index(['settlement_id', 'settlement_status']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
            $table->dropIndex(['settlement_status']);
            $table->dropIndex(['merchant_id', 'settlement_status']);
            $table->dropIndex(['settlement_id', 'settlement_status']);
            $table->dropColumn([
                'settlement_id',
                'settlement_status',
                'settled_at',
                'gst_amount',
                'other_fees'
            ]);
        });
    }
};
