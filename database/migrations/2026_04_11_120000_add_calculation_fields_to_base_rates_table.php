<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('base_rates', function (Blueprint $table) {
            $table->string('calculation_type', 32)
                ->default('percentage_fixed')
                ->after('currency')
                ->comment('percentage_only, percentage_fixed, fixed_only, tiered');
            $table->json('tier_slabs')->nullable()->after('calculation_type');
            $table->string('tier_fee_unit', 16)
                ->nullable()
                ->after('tier_slabs')
                ->comment('percent or fixed — meaning of fee_value when calculation_type=tiered');
        });

        // Existing rows behave as percentage + fixed (legacy behaviour).
        \Illuminate\Support\Facades\DB::table('base_rates')->update([
            'calculation_type' => 'percentage_fixed',
        ]);
    }

    public function down(): void
    {
        Schema::table('base_rates', function (Blueprint $table) {
            $table->dropColumn(['calculation_type', 'tier_slabs', 'tier_fee_unit']);
        });
    }
};
