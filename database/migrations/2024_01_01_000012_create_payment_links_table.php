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
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('link_token')->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->json('customer_details')->nullable();
            $table->enum('status', [
                'active',
                'expired',
                'paid',
                'cancelled'
            ])->default('active');
            $table->integer('usage_count')->default(0);
            $table->integer('max_usage')->nullable();
            $table->boolean('test_mode')->default(false);
            $table->json('metadata')->nullable();
            $table->string('success_url')->nullable();
            $table->string('cancel_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('merchant_id');
            $table->index('link_token');
            $table->index('status');
            $table->index('expires_at');
            $table->index(['merchant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};

