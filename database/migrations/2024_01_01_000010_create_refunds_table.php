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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('refund_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('initiated_by')->constrained('users');
            $table->json('gateway_response')->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->boolean('is_partial')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('transaction_id');
            $table->index('merchant_id');
            $table->index('refund_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['merchant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

