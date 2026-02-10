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
        Schema::create('merchant_vendors', function (Blueprint $table) {
            $table->id();

            // Link to main merchant
            $table->unsignedBigInteger('merchant_id')->comment('Parent merchant ID');

            // Basic vendor identity
            $table->string('vendor_code')->unique();
            $table->string('vendor_name');
            $table->string('vendor_email');
            $table->string('vendor_phone')->nullable();
            $table->string('vendor_address')->nullable();
            $table->string('vendor_pan_no')->nullable();
            $table->string('vendor_login_id')->nullable();

            // Descriptions / notes
            $table->string('vendor_description_1')->nullable();
            $table->string('vendor_description_2')->nullable();

            // Bank details (for payouts)
            $table->string('bank_account_number');
            $table->string('bank_account_ifsc');
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_account_holder_name');
            $table->enum('account_type', ['Savings Account', 'Current Account'])->default('Savings Account');
            $table->string('upi_id')->nullable();

            // Status / workflow
            $table->enum('status', ['pending', 'approved', 'disapproved'])->default('pending');
            $table->text('note')->nullable();
            $table->string('reference_id')->nullable();

            $table->timestamps();

            $table->index('merchant_id');
            $table->index('vendor_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_vendors');
    }
};


