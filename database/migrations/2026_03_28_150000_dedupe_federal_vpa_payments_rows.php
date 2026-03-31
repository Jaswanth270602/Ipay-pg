<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove older duplicate rows with the same merchant_id + reference_id (keeps highest id).
     */
    public function up(): void
    {
        if (! Schema::hasTable('federal_vpa_payments')) {
            return;
        }

        if (! Schema::hasColumn('federal_vpa_payments', 'reference_id')) {
            return;
        }

        do {
            $ids = DB::table('federal_vpa_payments as t1')
                ->join('federal_vpa_payments as t2', function ($join) {
                    $join->on('t1.merchant_id', '=', 't2.merchant_id')
                        ->on('t1.reference_id', '=', 't2.reference_id')
                        ->whereNotNull('t1.reference_id')
                        ->whereColumn('t1.id', '<', 't2.id');
                })
                ->pluck('t1.id');

            if ($ids->isEmpty()) {
                break;
            }

            DB::table('federal_vpa_payments')->whereIn('id', $ids)->delete();
        } while (true);
    }

    public function down(): void
    {
        // Data loss cannot be restored.
    }
};
