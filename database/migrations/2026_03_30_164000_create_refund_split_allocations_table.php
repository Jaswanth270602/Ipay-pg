<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_split_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained('refunds');
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('payment_split_id')->constrained('payment_splits');
            $table->enum('strategy', ['proportional', 'vendor_specific', 'platform_bear']);
            $table->decimal('allocated_amount', 15, 2);
            $table->enum('beneficiary_type', ['merchant', 'vendor', 'platform']);
            $table->unsignedBigInteger('beneficiary_id')->nullable();
            $table->timestamps();

            $table->index(['refund_id']);
            $table->index(['payment_split_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_split_allocations');
    }
};

