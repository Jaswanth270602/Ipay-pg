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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('settlement_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('transaction_count')->default(0);
            $table->integer('refund_count')->default(0);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'on_hold'
            ])->default('pending');
            $table->json('bank_details')->nullable();
            $table->string('utr_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('merchant_id');
            $table->index('settlement_id');
            $table->index('status');
            $table->index('period_start');
            $table->index('period_end');
            $table->index(['merchant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};

