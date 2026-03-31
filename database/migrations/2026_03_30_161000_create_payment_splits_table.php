<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->enum('beneficiary_type', ['merchant', 'vendor', 'platform']);
            $table->unsignedBigInteger('beneficiary_id')->nullable();
            $table->enum('split_rule_type', ['percentage', 'fixed']);
            $table->decimal('split_rule_value', 12, 4);
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency', 3);
            $table->enum('status', ['pending', 'posted', 'settled', 'refunded'])->default('pending');
            $table->string('idempotency_key', 80)->unique();
            $table->timestamps();

            $table->index(['transaction_id']);
            $table->index(['beneficiary_type', 'beneficiary_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_splits');
    }
};

