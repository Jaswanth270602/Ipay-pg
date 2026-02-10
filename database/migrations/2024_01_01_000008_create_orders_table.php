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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('order_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->json('customer_details')->nullable();
            $table->enum('status', [
                'created',
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'expired'
            ])->default('created');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('return_url')->nullable();
            $table->string('cancel_url')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->boolean('test_mode')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('merchant_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('test_mode');
            $table->index('created_at');
            $table->index(['merchant_id', 'status']);
            $table->unique('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

