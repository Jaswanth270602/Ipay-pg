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
        Schema::create('s2s_callback_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->nullable()->comment('Merchant ID');
            $table->string('merchant_name')->nullable()->comment('Merchant Name');
            $table->unsignedBigInteger('order_id')->nullable()->comment('Order ID');
            $table->string('tran_id')->nullable()->comment('Transaction ID from payment gateway');
            $table->string('transaction_id')->nullable()->comment('Internal Transaction ID');
            $table->text('callback_url')->comment('Callback URL');
            $table->dateTime('payment_datetime')->nullable()->comment('Payment Date and Time');
            $table->integer('http_status_code')->nullable()->comment('HTTP Status Code from callback response');
            $table->string('initiated_by')->nullable()->comment('Who initiated the callback (system/user)');
            $table->dateTime('callback_datetime')->nullable()->comment('Callback Date and Time');
            $table->longText('request_log')->nullable()->comment('Request payload/log');
            $table->longText('response_log')->nullable()->comment('Response payload/log');
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('order_id');
            $table->index('tran_id');
            $table->index('transaction_id');
            $table->index('payment_datetime');
            $table->index('callback_datetime');
            $table->index('http_status_code');
            $table->index('created_at');

            if (Schema::hasTable('merchants')) {
                $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
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
        Schema::dropIfExists('s2s_callback_logs');
    }
};
