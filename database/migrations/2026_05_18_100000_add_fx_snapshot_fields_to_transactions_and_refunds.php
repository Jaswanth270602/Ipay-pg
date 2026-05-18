<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 6)->nullable()->after('currency');
            $table->string('fx_base_currency', 3)->default('USD')->after('amount_base');
            $table->decimal('fx_rate_from_to_base', 18, 8)->nullable()->after('fx_base_currency');
            $table->json('fx_rates_snapshot')->nullable()->after('fx_rate_from_to_base');
            $table->timestamp('fx_captured_at')->nullable()->after('fx_rates_snapshot');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->decimal('amount_base', 18, 6)->nullable()->after('currency');
            $table->decimal('fx_rate_from_to_base', 18, 8)->nullable()->after('amount_base');
            $table->json('fx_rates_snapshot')->nullable()->after('fx_rate_from_to_base');
            $table->timestamp('fx_captured_at')->nullable()->after('fx_rates_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'amount_base',
                'fx_base_currency',
                'fx_rate_from_to_base',
                'fx_rates_snapshot',
                'fx_captured_at',
            ]);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn([
                'amount_base',
                'fx_rate_from_to_base',
                'fx_rates_snapshot',
                'fx_captured_at',
            ]);
        });
    }
};
