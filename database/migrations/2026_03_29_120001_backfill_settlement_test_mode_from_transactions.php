<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align settlements.test_mode with linked transactions (legacy rows created before test_mode column).
     */
    public function up(): void
    {
        $rows = DB::table('settlements')->select('id')->get();
        foreach ($rows as $row) {
            $mode = DB::table('transactions')
                ->where('settlement_id', $row->id)
                ->value('test_mode');
            if ($mode !== null) {
                DB::table('settlements')->where('id', $row->id)->update(['test_mode' => (bool) $mode]);
            }
        }

        $detailRows = DB::table('settlement_details')->select('id', 'settlement_id')->get();
        foreach ($detailRows as $dr) {
            if (! $dr->settlement_id) {
                continue;
            }
            $mode = DB::table('settlements')->where('id', $dr->settlement_id)->value('test_mode');
            if ($mode !== null) {
                DB::table('settlement_details')->where('id', $dr->id)->update(['test_mode' => (bool) $mode]);
            }
        }
    }

    public function down(): void
    {
        // No-op: data backfill is not reversible safely
    }
};
