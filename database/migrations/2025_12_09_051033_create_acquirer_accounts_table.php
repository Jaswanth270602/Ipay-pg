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
        Schema::create('acquirer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_id')->unique();
            $table->string('acquirer_name'); // Paytm, Switch, HDFC, etc.
            $table->string('team')->nullable();
            $table->text('description')->nullable();
            $table->string('whitelist_url')->nullable();
            $table->enum('mode', ['TEST', 'LIVE'])->default('TEST');
            $table->string('sector')->nullable(); // B2B, Education, etc.
            $table->string('hdfc_me_code')->nullable();
            $table->string('settlement_account_name')->nullable();
            $table->boolean('refund_allowed')->default(true);
            $table->boolean('settlements_to_be_created')->default(true);
            $table->boolean('mask_pii')->default(false);
            $table->text('email_ids')->nullable(); // Comma-separated emails
            $table->string('secret_key')->nullable();
            $table->string('salt')->nullable();
            $table->string('additional_key_1')->nullable();
            $table->string('additional_key_2')->nullable();
            $table->string('additional_key_3')->nullable();
            $table->text('additional_key_data')->nullable();
            $table->string('live_request_url')->nullable();
            $table->string('live_query_url')->nullable();
            $table->string('live_refund_url')->nullable();
            $table->string('test_request_url')->nullable();
            $table->string('test_query_url')->nullable();
            $table->string('test_refund_url')->nullable();
            $table->string('nodal_account')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('acquirer_name');
            $table->index('mode');
            $table->index('sector');
            $table->index('is_active');
        });

        // Pivot table for acquirer_account_merchant relationship
        Schema::create('acquirer_account_merchant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acquirer_account_id')->constrained('acquirer_accounts')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['acquirer_account_id', 'merchant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acquirer_account_merchant');
        Schema::dropIfExists('acquirer_accounts');
    }
};
