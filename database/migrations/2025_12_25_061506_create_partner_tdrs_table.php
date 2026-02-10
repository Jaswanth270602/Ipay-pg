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
        Schema::create('partner_tdrs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('partner_name')->nullable();
            $table->unsignedBigInteger('merchant_id');
            $table->string('merchant_name')->nullable();
            $table->string('category')->nullable()->comment('Merchant Category like B2B, B2C, etc.');
            $table->string('payment_mode')->nullable()->comment('Payment Mode like ATM Card, Credit Card, etc.');
            $table->string('payment_channel')->nullable()->comment('Payment Channel');
            $table->string('bank_code')->nullable();
            $table->string('bank_description')->nullable();
            $table->decimal('tdr_fixed_fee', 15, 2)->default(0)->comment('Partner TDR Fixed Fee');
            $table->decimal('tdr_percentage', 8, 4)->default(0)->comment('Partner TDR Percentage');
            $table->decimal('tdr_min_amount', 15, 2)->default(0)->comment('Partner TDR Min Amount');
            $table->decimal('tdr_max_amount', 15, 2)->default(99999999.99)->comment('Partner TDR Max Amount');
            $table->decimal('min_transaction_amount', 15, 2)->default(0)->comment('Min Transaction Amount');
            $table->decimal('max_transaction_amount', 15, 2)->default(99999999.99)->comment('Max Transaction Amount');
            $table->decimal('min_transaction_charge', 15, 2)->default(0)->comment('Partner Min Transaction Charge');
            $table->decimal('max_transaction_charge', 15, 2)->default(99999999.99)->comment('Partner Max Transaction Charge');
            $table->decimal('overall_profit_share_percentage', 8, 4)->default(0)->comment('Overall Profit Share Percentage');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('partner_id');
            $table->index('merchant_id');
            $table->index('category');
            $table->index('payment_mode');
            $table->index('payment_channel');
            $table->index('bank_code');
            $table->index('is_active');
            
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_tdrs');
    }
};
