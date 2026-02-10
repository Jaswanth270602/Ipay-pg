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
        Schema::create('partner_settlement_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_settlement_id')->nullable()->comment('Link to partner_settlements table');
            $table->unsignedBigInteger('partner_id');
            $table->string('partner_name')->nullable();
            $table->unsignedBigInteger('merchant_id');
            $table->string('merchant_name')->nullable();
            $table->string('merchant_category')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('transaction_txn_id')->nullable()->comment('Transaction Txn ID');
            $table->string('settlement_record_id')->nullable()->comment('Settlement Record ID');
            $table->decimal('transaction_amount', 15, 2)->default(0)->comment('Transaction Amount');
            $table->decimal('partner_tdr_percentage', 8, 4)->default(0)->comment('Partner TDR Percentage');
            $table->decimal('partner_tdr_fixed_fee', 15, 2)->default(0)->comment('Partner TDR Fixed Fee');
            $table->decimal('partner_tdr_amount', 15, 2)->default(0)->comment('Partner TDR Amount');
            $table->decimal('merchant_tdr_percentage', 8, 4)->default(0)->comment('Merchant TDR Percentage');
            $table->decimal('merchant_tdr_fixed_fee', 15, 2)->default(0)->comment('Merchant TDR Fixed Fee');
            $table->decimal('merchant_tdr_amount', 15, 2)->default(0)->comment('Merchant TDR Amount');
            $table->decimal('tdr_amount', 15, 2)->default(0)->comment('Total TDR Amount');
            $table->decimal('partner_revenue', 15, 2)->default(0)->comment('Partner Revenue');
            $table->string('bank_code')->nullable();
            $table->string('payment_mode')->nullable()->comment('Mode Of Payment');
            $table->string('payment_channel')->nullable();
            $table->dateTime('payment_datetime')->nullable();
            $table->string('organization_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('partner_settlement_id');
            $table->index('partner_id');
            $table->index('merchant_id');
            $table->index('transaction_id');
            $table->index('transaction_txn_id');
            $table->index('settlement_record_id');
            $table->index('merchant_category');
            $table->index('payment_mode');
            $table->index('bank_code');
            $table->index('organization_name');
            $table->index('payment_datetime');
            
            $table->foreign('partner_settlement_id')->references('id')->on('partner_settlements')->onDelete('cascade');
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            if (Schema::hasTable('transactions')) {
                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_settlement_details');
    }
};
