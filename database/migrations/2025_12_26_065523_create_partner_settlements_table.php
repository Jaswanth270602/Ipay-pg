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
        Schema::create('partner_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_id')->unique()->comment('Unique Settlement ID');
            $table->unsignedBigInteger('partner_id');
            $table->string('partner_name')->nullable();
            $table->string('organization_name')->nullable();
            $table->decimal('settlement_amount', 15, 2)->default(0)->comment('Total Settlement Amount');
            $table->decimal('net_settlement_amount', 15, 2)->default(0)->comment('Net Settlement Amount after deductions');
            $table->decimal('tds_percentage', 5, 2)->default(0)->comment('TDS Percentage');
            $table->decimal('tds_amount', 15, 2)->default(0)->comment('TDS Amount');
            $table->decimal('gst_amount', 15, 2)->default(0)->comment('GST Amount');
            $table->enum('settlement_status', ['pending', 'settled', 'bounced', 'processing', 'failed'])->default('pending');
            $table->date('settlement_date')->nullable();
            $table->dateTime('settlement_start_time')->nullable();
            $table->dateTime('settlement_end_time')->nullable();
            $table->string('bank_reference_id')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('transfer_method')->nullable()->comment('IMPS, NEFT, RTGS, etc.');
            $table->enum('transfer_status', ['pending', 'initiated', 'completed', 'failed'])->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('partner_id');
            $table->index('settlement_id');
            $table->index('settlement_status');
            $table->index('settlement_date');
            $table->index('organization_name');
            $table->index('transfer_status');
            
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_settlements');
    }
};
