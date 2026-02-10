<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Merchant -> Acquirer: many-to-one (one merchant has exactly one acquirer at a time).
     */
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->foreignId('acquirer_account_id')->nullable()->after('test_mode')
                ->constrained('acquirer_accounts')->nullOnDelete();
        });

        // Backfill: set acquirer_account_id from pivot (one per merchant; take first)
        $firstPerMerchant = DB::table('acquirer_account_merchant')
            ->select('merchant_id', 'acquirer_account_id')
            ->orderBy('merchant_id')
            ->orderBy('acquirer_account_id')
            ->get()
            ->unique('merchant_id');
        foreach ($firstPerMerchant as $row) {
            DB::table('merchants')->where('id', $row->merchant_id)->update([
                'acquirer_account_id' => $row->acquirer_account_id,
            ]);
        }
        // Sync pivot: each merchant only in one acquirer (keep one row per merchant)
        foreach (DB::table('merchants')->whereNotNull('acquirer_account_id')->pluck('acquirer_account_id', 'id') as $merchantId => $acquirerId) {
            DB::table('acquirer_account_merchant')->where('merchant_id', $merchantId)->delete();
            DB::table('acquirer_account_merchant')->insert([
                'merchant_id' => $merchantId,
                'acquirer_account_id' => $acquirerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropForeign(['acquirer_account_id']);
        });
    }
};
