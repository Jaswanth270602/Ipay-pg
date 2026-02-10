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
        Schema::table('base_rates', function (Blueprint $table) {
            // High-level classification
            $table->string('payment_mode')->nullable()->after('payment_method')->comment('e.g. Netbanking, Credit Card, Debit Card, Wallet, etc.');
            $table->string('sector')->nullable()->after('service_type')->comment('Merchant sector / category');
            $table->string('currency')->nullable()->after('transaction_type')->comment('ISO currency code like INR, USD, EUR');

            // Range based configuration
            $table->decimal('min_amount', 14, 2)->nullable()->after('flat_fee');
            $table->decimal('max_amount', 14, 2)->nullable()->after('min_amount');
            $table->decimal('min_share', 8, 4)->nullable()->after('max_amount');
            $table->decimal('max_share', 8, 4)->nullable()->after('min_share');

            // Optional grouping / mapping to upstream bank config
            $table->unsignedBigInteger('team_id')->nullable()->after('entity_id')->comment('Optional team / group id for mapping');
            $table->string('team_name')->nullable()->after('team_id');
            $table->string('bank_code')->nullable()->after('team_name');
            $table->string('bank_description')->nullable()->after('bank_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_rates', function (Blueprint $table) {
            $table->dropColumn([
                'payment_mode',
                'sector',
                'currency',
                'min_amount',
                'max_amount',
                'min_share',
                'max_share',
                'team_id',
                'team_name',
                'bank_code',
                'bank_description',
            ]);
        });
    }
};


