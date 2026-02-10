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
            $table->tinyInteger('settlement_cycle_domestic')->default(1)->after('fee_flat')->comment('T+X days for domestic settlements (1 = T+1, 2 = T+2, etc.)');
            $table->tinyInteger('settlement_cycle_international')->default(7)->after('settlement_cycle_domestic')->comment('T+X days for international settlements (up to 7 days)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['settlement_cycle_domestic', 'settlement_cycle_international']);
        });
    }
};
