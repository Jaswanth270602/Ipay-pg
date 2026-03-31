<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions');
            $table->foreignId('refund_id')->nullable()->constrained('refunds');
            $table->unsignedBigInteger('vendor_settlement_id')->nullable();
            $table->enum('entry_type', [
                'PAYMENT_CAPTURED',
                'SETTLEMENT_RELEASED',
                'REFUND_DEBIT',
                'REFUND_REVERSAL',
                'MANUAL_ADJUSTMENT',
            ]);
            $table->enum('dr_cr', ['DR', 'CR']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->decimal('pending_delta', 15, 2)->default(0);
            $table->decimal('available_delta', 15, 2)->default(0);
            $table->decimal('settled_delta', 15, 2)->default(0);
            $table->string('notes', 500)->nullable();
            $table->string('idempotency_key', 80)->unique();
            $table->timestamps();

            $table->index(['vendor_id', 'id']);
            $table->index(['merchant_id', 'id']);
        });

        Schema::create('vendor_balances', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->primary();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->decimal('pending_amount', 15, 2)->default(0);
            $table->decimal('available_amount', 15, 2)->default(0);
            $table->decimal('settled_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_balances');
        Schema::dropIfExists('vendor_ledger_entries');
    }
};

