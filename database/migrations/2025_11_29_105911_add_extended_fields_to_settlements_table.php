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
        Schema::table('settlements', function (Blueprint $table) {
            // Partner fields
            $table->string('partner_id')->nullable()->after('merchant_id');
            $table->string('partner_name')->nullable()->after('partner_id');
            
            // Bank account details
            $table->string('bank_reference')->nullable()->after('utr_number');
            $table->string('account_name')->nullable()->after('bank_reference');
            $table->string('account_number')->nullable()->after('account_name');
            $table->string('ifsc_code')->nullable()->after('account_number');
            $table->string('bank_name')->nullable()->after('ifsc_code');
            $table->string('bank_branch')->nullable()->after('bank_name');
            
            // Settlement details
            $table->text('settlement_description')->nullable()->after('bank_branch');
            $table->date('payment_start_date')->nullable()->after('settlement_description');
            $table->date('payment_end_date')->nullable()->after('payment_start_date');
            $table->date('settlement_date')->nullable()->after('payment_end_date');
            $table->decimal('payout_amount', 15, 2)->nullable()->after('net_amount');
            
            // Settlement status
            $table->enum('settlement_status', ['pending', 'settled', 'bounced', 'processing'])->default('pending')->after('status');
            
            // Indexes
            $table->index('partner_id');
            $table->index('settlement_status');
            $table->index('settlement_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropColumn([
                'partner_id', 'partner_name',
                'bank_reference', 'account_name', 'account_number', 'ifsc_code', 'bank_name', 'bank_branch',
                'settlement_description', 'payment_start_date', 'payment_end_date', 'settlement_date', 'payout_amount',
                'settlement_status'
            ]);
        });
    }
};
