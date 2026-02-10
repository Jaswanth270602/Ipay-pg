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
        Schema::create('partner_team_profit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('partner_name')->nullable();
            $table->unsignedBigInteger('merchant_id');
            $table->string('merchant_name')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('transaction_txn_id')->nullable();
            $table->string('transaction_sequence_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('order_order_id')->nullable();
            $table->dateTime('payment_datetime')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('payment_channel')->nullable();
            $table->decimal('merchant_tdr_percentage', 8, 4)->default(0)->comment('Merchant TDR Percentage');
            $table->decimal('merchant_tdr_fixed_fee', 15, 2)->default(0)->comment('Merchant TDR Fixed Fee');
            $table->decimal('merchant_tdr_amount', 15, 2)->default(0)->comment('Merchant TDR Amount');
            $table->decimal('partner_base_rate_percentage', 8, 4)->default(0)->comment('Partner Base Rate Percentage');
            $table->decimal('partner_base_rate_fixed_fee', 15, 2)->default(0)->comment('Partner Base Rate Fixed Fee');
            $table->decimal('partner_tdr_amount', 15, 2)->default(0)->comment('Partner TDR Amount');
            $table->decimal('profit', 15, 2)->default(0)->comment('Profit = Merchant TDR - Partner TDR');
            $table->decimal('transaction_amount', 15, 2)->default(0)->comment('Transaction Amount');
            $table->timestamps();
            
            $table->index('partner_id');
            $table->index('merchant_id');
            $table->index('transaction_id');
            $table->index('order_id');
            $table->index('payment_datetime');
            $table->index('payment_mode');
            $table->index('payment_channel');
            
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            if (Schema::hasTable('transactions')) {
                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            }
            if (Schema::hasTable('orders')) {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_team_profit');
    }
};
