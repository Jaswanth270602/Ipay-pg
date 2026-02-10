<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add column as nullable first
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('merchant_unique_id', 20)->nullable()->after('id');
        });

        // Generate merchant_unique_id for existing merchants
        $merchants = DB::table('merchants')->whereNull('merchant_unique_id')->orderBy('id')->get();
        $counter = 1;
        
        foreach ($merchants as $merchant) {
            $merchantId = 'BC_MID_' . str_pad($counter, 6, '0', STR_PAD_LEFT);
            DB::table('merchants')
                ->where('id', $merchant->id)
                ->update(['merchant_unique_id' => $merchantId]);
            $counter++;
        }

        // Make the column not nullable
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('merchant_unique_id', 20)->nullable(false)->change();
        });

        // Add unique constraint separately
        Schema::table('merchants', function (Blueprint $table) {
            $table->unique('merchant_unique_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('merchant_unique_id');
        });
    }
};
