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
        Schema::create('acquirer_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquirer_account_id')->constrained('acquirer_accounts')->onDelete('cascade');
            $table->string('payment_mode'); // Netbanking, Credit Card, etc.
            $table->string('bank_code')->nullable();
            $table->string('bank_description')->nullable();
            $table->string('acquirer_name'); // From acquirer_accounts
            $table->string('account_id'); // From acquirer_accounts
            $table->string('account_description')->nullable();
            $table->string('sector')->nullable();
            $table->string('settlement_time_frame')->default('t+1'); // t+1, t+2, etc.
            $table->string('settlement_time_of_day')->nullable(); // e.g., "09:00", "EOD"
            $table->decimal('fixed_fee_mdr', 10, 4)->default(0); // Fixed Fee TDR
            $table->decimal('percentage_mdr', 8, 4)->default(0); // Percentage TDR
            $table->decimal('service_tax_rates', 8, 4)->default(0);
            $table->decimal('min_amount', 15, 2)->nullable(); // TDR Min Amount
            $table->decimal('max_amount', 15, 2)->nullable(); // TDR Max Amount
            $table->decimal('min_transaction_charge', 10, 2)->nullable(); // TDR Min Transaction Amount
            $table->decimal('max_transaction_charge', 10, 2)->nullable(); // TDR Max Transaction Amount
            $table->boolean('is_enabled')->default(true);
            $table->string('part_paid_id')->nullable();
            $table->timestamps();

            $table->index('acquirer_account_id');
            $table->index('payment_mode');
            $table->index('bank_code');
            $table->index('acquirer_name');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acquirer_rates');
    }
};
