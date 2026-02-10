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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('txn_id')->index();
            $table->string('gateway')->nullable()->after('status');
            $table->string('gateway_transaction_id')->nullable()->after('gateway');
            $table->string('customer_email')->nullable()->after('payment_details');
            $table->string('customer_phone')->nullable()->after('customer_email');
            $table->timestamp('processed_at')->nullable()->after('captured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_id',
                'gateway',
                'gateway_transaction_id',
                'customer_email',
                'customer_phone',
                'processed_at',
            ]);
        });
    }
};
