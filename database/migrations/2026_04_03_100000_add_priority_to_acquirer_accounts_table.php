<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acquirer_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('acquirer_accounts', 'priority')) {
                $table->unsignedInteger('priority')->nullable()->after('mode');
                $table->index('priority');
            }
        });

        // Backfill sequential priorities per mode so routing has a deterministic order.
        $modes = ['TEST', 'LIVE'];
        foreach ($modes as $mode) {
            $rows = DB::table('acquirer_accounts')
                ->where('mode', $mode)
                ->orderBy('id')
                ->get(['id', 'priority']);

            $priority = 1;
            foreach ($rows as $row) {
                DB::table('acquirer_accounts')
                    ->where('id', $row->id)
                    ->update(['priority' => $priority]);
                $priority++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('acquirer_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('acquirer_accounts', 'priority')) {
                $table->dropIndex(['priority']);
                $table->dropColumn('priority');
            }
        });
    }
};

