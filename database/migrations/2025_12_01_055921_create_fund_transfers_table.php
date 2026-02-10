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
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('transfer_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('from_account_name')->nullable();
            $table->string('from_account_number')->nullable();
            $table->string('from_ifsc_code')->nullable();
            $table->string('from_bank_name')->nullable();
            $table->string('to_account_name');
            $table->string('to_account_number');
            $table->string('to_ifsc_code');
            $table->string('to_bank_name')->nullable();
            $table->string('to_bank_branch')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('utr_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->date('transfer_date')->nullable();
            $table->date('processed_date')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
    }
};
