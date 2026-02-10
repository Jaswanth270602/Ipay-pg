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
        Schema::create('provider_responses', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index(); // razorpay, paytm, etc.
            $table->foreignId('acquirer_account_id')->nullable()->constrained('acquirer_accounts')->onDelete('set null');
            $table->string('event_type')->index(); // payment.success, refund.created, etc.
            $table->string('provider_event_type')->nullable(); // Original provider event type
            $table->json('raw_payload'); // Complete raw payload from provider
            $table->string('normalized_status')->nullable()->index(); // success, failed, pending, etc.
            $table->string('provider_status')->nullable(); // Provider-specific status
            $table->string('payment_id')->nullable()->index(); // Gateway payment ID
            $table->string('order_id')->nullable()->index(); // Gateway order ID
            $table->string('refund_id')->nullable()->index(); // Gateway refund ID
            $table->string('settlement_id')->nullable()->index(); // Gateway settlement ID
            $table->string('dispute_id')->nullable()->index(); // Gateway dispute ID
            $table->string('signature')->nullable(); // Webhook signature
            $table->boolean('signature_verified')->default(false); // Whether signature was verified
            $table->string('ip_address')->nullable(); // IP address of webhook sender
            $table->text('error_message')->nullable(); // Error message if processing failed
            $table->boolean('processed')->default(false)->index(); // Whether callback was processed
            $table->timestamp('processed_at')->nullable(); // When callback was processed
            $table->timestamps();

            // Indexes for common queries
            $table->index(['provider', 'event_type']);
            $table->index(['provider', 'normalized_status']);
            $table->index(['provider', 'processed']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_responses');
    }
};
