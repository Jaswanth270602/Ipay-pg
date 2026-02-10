<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // KYC Fields
            $table->enum('kyc_status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending')->after('status');
            $table->string('kyc_document_type')->nullable()->after('kyc_status');
            $table->string('kyc_document_number')->nullable()->after('kyc_document_type');
            $table->string('kyc_document_file')->nullable()->after('kyc_document_number');
            
            // Additional Business Details
            $table->string('business_type')->nullable()->after('company_name');
            $table->string('tax_id')->nullable()->after('business_type');
            $table->string('business_registration_number')->nullable()->after('tax_id');
            $table->text('business_address')->nullable()->after('business_registration_number');
            $table->string('business_city')->nullable()->after('business_address');
            $table->string('business_state')->nullable()->after('business_city');
            $table->string('business_country')->nullable()->after('business_state');
            $table->string('business_postal_code')->nullable()->after('business_country');
            $table->string('business_phone')->nullable()->after('business_postal_code');
            $table->string('business_website')->nullable()->after('business_phone');
            
            // Bank Account Details
            $table->string('bank_account_holder_name')->nullable()->after('business_website');
            $table->string('bank_account_number')->nullable()->after('bank_account_holder_name');
            $table->string('bank_ifsc_code')->nullable()->after('bank_account_number');
            $table->string('bank_name')->nullable()->after('bank_ifsc_code');
            $table->string('bank_branch')->nullable()->after('bank_name');
            
            // Card Details (for merchant onboarding payment if needed)
            $table->string('card_holder_name')->nullable()->after('bank_branch');
            $table->string('card_number_encrypted')->nullable()->after('card_holder_name');
            $table->string('card_expiry_month')->nullable()->after('card_number_encrypted');
            $table->string('card_expiry_year')->nullable()->after('card_expiry_month');
            $table->string('card_cvv_encrypted')->nullable()->after('card_expiry_year');
            
            // Onboarding Status
            $table->enum('onboarding_status', ['pending', 'in_progress', 'completed'])->default('pending')->after('kyc_status');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_status');
            $table->json('onboarding_steps')->nullable()->after('onboarding_completed_at');
            
            // Additional indexes
            $table->index('kyc_status');
            $table->index('onboarding_status');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_status', 'kyc_document_type', 'kyc_document_number', 'kyc_document_file',
                'business_type', 'tax_id', 'business_registration_number',
                'business_address', 'business_city', 'business_state', 'business_country',
                'business_postal_code', 'business_phone', 'business_website',
                'bank_account_holder_name', 'bank_account_number', 'bank_ifsc_code',
                'bank_name', 'bank_branch',
                'card_holder_name', 'card_number_encrypted', 'card_expiry_month',
                'card_expiry_year', 'card_cvv_encrypted',
                'onboarding_status', 'onboarding_completed_at', 'onboarding_steps'
            ]);
        });
    }
};

