<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin + merchant shares must sum to 100%. Older rows used admin + reseller + merchant = 100%.
     * Rescale admin and merchant proportionally so they total 100%; reseller_share_pct is unchanged.
     */
    public function up(): void
    {
        if (! Schema::hasTable('merchant_reseller_splits')) {
            return;
        }

        DB::table('merchant_reseller_splits')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $admin = (float) $row->admin_share_pct;
                $merchant = (float) $row->merchant_share_pct;
                $sum = $admin + $merchant;

                if ($sum <= 0.00001) {
                    DB::table('merchant_reseller_splits')->where('id', $row->id)->update([
                        'admin_share_pct' => 100,
                        'merchant_share_pct' => 0,
                        'updated_at' => now(),
                    ]);

                    continue;
                }

                if (abs($sum - 100.0) > 0.0001) {
                    DB::table('merchant_reseller_splits')->where('id', $row->id)->update([
                        'admin_share_pct' => round($admin / $sum * 100, 4),
                        'merchant_share_pct' => round($merchant / $sum * 100, 4),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Normalization is not safely reversible.
    }
};
