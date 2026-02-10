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
        Schema::table('merchants', function (Blueprint $table) {
            // Partner and Team fields
            $table->boolean('is_partner_merchant')->default(false)->after('status');
            $table->string('partner_id')->nullable()->after('is_partner_merchant');
            $table->string('partner_name')->nullable()->after('partner_id');
            $table->string('team_id')->nullable()->after('partner_name');
            $table->string('team_name')->nullable()->after('team_id');
            
            // Merchant basic info
            $table->string('legal_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('merchant_category')->nullable()->after('phone');
            $table->string('merchant_category_code')->nullable()->after('merchant_category');
            $table->string('ownership_type')->nullable()->after('merchant_category_code');
            $table->string('organization_name')->nullable()->after('company_name');
            $table->string('challan_urn')->nullable()->after('organization_name');
            
            // Address fields
            $table->string('address_line_1')->nullable()->after('business_address');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            
            // Tax identification fields
            $table->string('merchant_pan_number')->nullable()->after('tax_id');
            $table->string('name_on_pan_card')->nullable()->after('merchant_pan_number');
            $table->string('gst_identification_no')->nullable()->after('name_on_pan_card');
            $table->string('gstin_state')->nullable()->after('gst_identification_no');
            $table->string('tan_no')->nullable()->after('gstin_state');
            
            // Contact information
            $table->string('contact_name')->nullable()->after('business_phone');
            $table->string('contact_mobile')->nullable()->after('contact_name');
            $table->string('contact_landline')->nullable()->after('contact_mobile');
            $table->string('contact_email')->nullable()->after('contact_landline');
            
            // Settlement bank account
            $table->boolean('is_dummy_account')->default(false)->after('bank_branch');
            $table->enum('account_type', ['Savings Account', 'Current Account'])->nullable()->after('bank_account_holder_name');
            
            // Merchant type (Merchants vs Vendor Merchants)
            $table->enum('merchant_type', ['merchant', 'vendor_merchant'])->default('merchant')->after('status');
            
            // Approval status
            $table->enum('approval_status', ['not_approved', 'approved', 'test_approved', 'rejected'])->default('not_approved')->after('status');
            
            // Registration date
            $table->date('registration_date')->nullable()->after('challan_urn');
            
            // Indexes
            $table->index('merchant_type');
            $table->index('approval_status');
            $table->index('partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'is_partner_merchant', 'partner_id', 'partner_name', 'team_id', 'team_name',
                'legal_name', 'phone', 'merchant_category', 'merchant_category_code', 'ownership_type',
                'organization_name', 'challan_urn', 'address_line_1', 'address_line_2',
                'merchant_pan_number', 'name_on_pan_card', 'gst_identification_no', 'gstin_state', 'tan_no',
                'contact_name', 'contact_mobile', 'contact_landline', 'contact_email',
                'is_dummy_account', 'account_type', 'merchant_type', 'approval_status', 'registration_date'
            ]);
        });
    }
};
