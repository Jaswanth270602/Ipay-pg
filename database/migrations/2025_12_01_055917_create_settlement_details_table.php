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
        Schema::create('settlement_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('settlement_id')->nullable()->constrained('settlements');
            $table->string('order_id')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->string('tran_seq_id')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->string('transaction_qualifier')->nullable();
            $table->string('settlement_qualifier')->nullable();
            $table->string('setl_id')->nullable();
            $table->decimal('amount_paid_by_customer', 15, 2)->default(0);
            $table->decimal('settlement_amount', 15, 2)->default(0);
            $table->date('bank_settlement_date')->nullable();
            $table->decimal('bank_settlement_amount', 15, 2)->default(0);
            $table->string('bank_reference')->nullable();
            $table->string('settlement_account_name')->nullable();
            $table->string('settlement_account_number')->nullable();
            $table->string('settlement_ifsc_code')->nullable();
            $table->string('settlement_bank_name')->nullable();
            $table->string('settlement_bank_branch')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('payment_channel')->nullable();
            $table->decimal('tdr_percentage', 5, 2)->default(0);
            $table->decimal('tdr_fixed_fee', 10, 2)->default(0);
            $table->decimal('tdr_amount', 15, 2)->default(0);
            $table->date('earliest_priority_settlement_date')->nullable();
            $table->date('latest_priority_settlement_date')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('setd_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('account_id')->nullable();
            $table->string('acq_payment_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_details');
    }
};
