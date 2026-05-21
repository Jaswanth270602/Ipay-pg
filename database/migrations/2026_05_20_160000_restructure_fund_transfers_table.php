<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align fund_transfers with Admin Fund Transfer UI (FundTransferController).
 * Previous migration used p2p-style columns (transfer_id, amount, to_account_number); app expects settlement-admin columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fund_transfers');

        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('reference_id')->unique();
            $table->string('transfer_reference_id')->nullable();
            $table->string('transfer_qualifier');
            $table->string('purpose_of_payment')->nullable();
            $table->string('transfer_reference_no')->nullable();
            $table->string('transfer_mode')->nullable()->default('SFTI ADJ');
            $table->date('transfer_date');
            $table->decimal('transfer_amount', 15, 2)->default(0);
            $table->decimal('credited_amount', 15, 2)->default(0);
            $table->decimal('debited_amount', 15, 2)->default(0);
            $table->string('to_account')->nullable();
            $table->string('bank_name_ca')->nullable();
            $table->string('fund_received', 10)->default('No');
            $table->string('fund_received_with_commission', 10)->default('No');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'transfer_date']);
            $table->index('transfer_qualifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');

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
};
