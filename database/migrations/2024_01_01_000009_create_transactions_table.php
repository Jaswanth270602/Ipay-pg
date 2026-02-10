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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('txn_id')->unique();
            $table->enum('payment_method', [
                'card',
                'netbanking',
                'upi',
                'wallet',
                'emi'
            ]);
            $table->decimal('amount', 15, 2);
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', [
                'initiated',
                'pending',
                'authorized',
                'captured',
                'success',
                'failed',
                'cancelled'
            ])->default('initiated');
            $table->json('gateway_response')->nullable();
            $table->json('payment_details')->nullable(); // last4, card_type, bank_name etc
            $table->string('gateway_txn_id')->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('banks');
            $table->boolean('test_mode')->default(false);
            $table->string('idempotency_key')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('merchant_id');
            $table->index('txn_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('test_mode');
            $table->index('created_at');
            $table->index(['merchant_id', 'status']);
            $table->index(['merchant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

