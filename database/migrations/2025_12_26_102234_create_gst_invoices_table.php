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
        Schema::create('gst_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique()->comment('Unique Invoice Number');
            $table->unsignedTinyInteger('month')->comment('Month (1-12)');
            $table->unsignedSmallInteger('year')->comment('Year (e.g., 2024)');
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->string('gst_provided_by')->nullable()->comment('GST Provided By');
            $table->string('gst_payer_name')->nullable()->comment('GST Payer Name');
            $table->string('payer_gstin')->nullable()->comment('Payer GSTIN (15 characters)');
            $table->string('payer_gstin_state')->nullable()->comment('Payer GSTIN State');
            $table->decimal('non_taxable_tdr', 15, 2)->default(0)->comment('Non-Taxable TDR');
            $table->decimal('taxable_tdr', 15, 2)->default(0)->comment('Taxable TDR');
            $table->decimal('sgst', 15, 2)->default(0)->comment('State GST (50% of GST)');
            $table->decimal('cgst', 15, 2)->default(0)->comment('Central GST (50% of GST)');
            $table->decimal('igst', 15, 2)->default(0)->comment('Integrated GST');
            $table->decimal('utgst', 15, 2)->default(0)->comment('Union Territory GST');
            $table->decimal('invoice_value', 15, 2)->default(0)->comment('Total Invoice Value');
            $table->date('invoice_date')->nullable()->comment('Invoice Date');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('invoice_number');
            $table->index(['month', 'year']);
            $table->index('merchant_id');
            $table->index('payer_gstin');
            $table->index('payer_gstin_state');
            $table->index('invoice_date');
            
            if (Schema::hasTable('merchants')) {
                $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gst_invoices');
    }
};
