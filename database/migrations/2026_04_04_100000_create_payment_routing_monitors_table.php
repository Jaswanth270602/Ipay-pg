<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_routing_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('txn_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->foreignId('final_acquirer_account_id')->nullable()->constrained('acquirer_accounts')->nullOnDelete();
            $table->string('final_acquirer_name')->nullable();
            $table->string('status', 32)->default('failed'); // success, failed, pending
            $table->json('flow_trace')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('test_mode')->default(false);
            $table->string('source', 64)->default('orchestration_api');
            $table->timestamps();

            $table->index(['merchant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_routing_monitors');
    }
};
