<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 40)->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('refund_deduction', 15, 2)->default(0);
            $table->decimal('net_payout', 15, 2)->default(0);
            $table->string('currency', 3);
            $table->enum('status', ['pending', 'processing', 'settled', 'failed', 'on_hold'])->default('pending');
            $table->date('settlement_date');
            $table->timestamp('processed_at')->nullable();
            $table->string('bank_reference', 120)->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status', 'settlement_date']);
        });

        Schema::create('vendor_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_settlement_id')->constrained('vendor_settlements');
            $table->foreignId('payment_split_id')->constrained('payment_splits');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['vendor_settlement_id', 'payment_split_id'], 'uq_vendor_settlement_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_settlement_items');
        Schema::dropIfExists('vendor_settlements');
    }
};

