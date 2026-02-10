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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('payment_link_id')->nullable()->after('merchant_id')->constrained('payment_links')->onDelete('set null');
            $table->string('payment_method')->nullable()->after('currency'); // card, upi, netbanking, wallet
            $table->json('payment_details')->nullable()->after('payment_method'); // Store payment-specific details
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_link_id']);
            $table->dropColumn(['payment_link_id', 'payment_method', 'payment_details']);
        });
    }
};
