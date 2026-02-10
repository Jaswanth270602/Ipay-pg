<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chargebacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->string('chargeback_request_id')->unique();
            $table->enum('refunded_or_not', ['Yes', 'No'])->nullable();
            $table->string('debit_settlement_id')->nullable();
            $table->enum('decision_in_favour_of', ['Merchant', 'Customer', 'Bank'])->nullable();
            $table->enum('chargeback_status', ['pending', 'contested', 'won', 'lost', 'processing'])->default('pending');
            $table->enum('contested', ['Yes', 'No'])->default('No');
            $table->string('account_id')->nullable();
            $table->string('account_id_descript')->nullable();
            $table->date('merchant_debit_date')->nullable();
            $table->date('merchant_credit_date')->nullable();
            $table->date('bank_debit_date')->nullable();
            $table->date('bank_credit_date')->nullable();
            $table->date('target_date')->nullable();
            $table->enum('debit_merchant', ['Yes', 'No'])->default('No');
            $table->enum('is_dispute', ['Yes', 'No'])->default('No');
            $table->enum('second_chargeback', ['Yes', 'No'])->default('No');
            $table->decimal('chargeback_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('merchant_id');
            $table->index('chargeback_status');
            $table->index('chargeback_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chargebacks');
    }
};
