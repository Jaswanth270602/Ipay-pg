<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('merchant_reseller_splits')) {
            Schema::create('merchant_reseller_splits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('base_rate_id')->constrained('base_rates')->cascadeOnDelete();
                $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->nullable()->constrained('resellers')->nullOnDelete();
                $table->decimal('admin_share_pct', 7, 4)->default(100);
                $table->decimal('reseller_share_pct', 7, 4)->default(0);
                $table->decimal('merchant_share_pct', 7, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('base_rate_id');
                $table->index(['merchant_id', 'reseller_id']);
            });
        }

        // Backfill defaults for existing merchant base rate rows.
        $rows = DB::table('base_rates')
            ->where('rate_type', 'merchant')
            ->where('entity_type', 'merchant')
            ->whereNotNull('entity_id')
            ->get(['id', 'entity_id']);

        foreach ($rows as $row) {
            $exists = DB::table('merchant_reseller_splits')->where('base_rate_id', $row->id)->exists();
            if ($exists) {
                continue;
            }

            $resellerId = DB::table('merchants')->where('id', $row->entity_id)->value('reseller_id');
            DB::table('merchant_reseller_splits')->insert([
                'base_rate_id' => $row->id,
                'merchant_id' => $row->entity_id,
                'reseller_id' => $resellerId,
                'admin_share_pct' => $resellerId ? 99 : 100,
                'reseller_share_pct' => $resellerId ? 1 : 0,
                'merchant_share_pct' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_reseller_splits');
    }
};
