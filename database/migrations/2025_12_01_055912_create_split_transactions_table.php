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
        Schema::create('split_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('split_id')->unique();
            $table->string('order_id');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('primary_amount', 15, 2);
            $table->decimal('secondary_amount', 15, 2);
            $table->foreignId('primary_merchant_id')->constrained('merchants');
            $table->foreignId('secondary_merchant_id')->nullable()->constrained('merchants');
            $table->decimal('primary_percentage', 5, 2)->default(100);
            $table->decimal('secondary_percentage', 5, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('split_transactions');
    }
};
