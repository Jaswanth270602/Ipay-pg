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
        Schema::create('base_rates', function (Blueprint $table) {
            $table->id();
            $table->string('rate_type')->comment('bank, merchant, receiver, pricer');
            $table->string('entity_type')->nullable()->comment('merchant_id, bank_id, receiver_id, etc.');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('ID of the entity (merchant, bank, etc.)');
            $table->string('payment_method')->comment('card, upi, netbanking, wallet');
            $table->string('service_type')->default('payment')->comment('payment, refund, chargeback, etc.');
            $table->string('transaction_type')->default('domestic')->comment('domestic, international');
            $table->decimal('percentage_fee', 5, 3)->default(0)->comment('Percentage fee (e.g., 2.50 for 2.5%)');
            $table->decimal('flat_fee', 10, 2)->default(0)->comment('Flat fee amount');
            $table->decimal('gst_percentage', 5, 2)->default(18)->comment('GST percentage on fees');
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['rate_type', 'entity_type', 'entity_id']);
            $table->index(['payment_method', 'service_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_rates');
    }
};
