<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchants') && ! Schema::hasColumn('merchants', 'reseller_id')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->foreignId('reseller_id')->nullable()->after('acquirer_account_id')->constrained('resellers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('reseller_merchant') && Schema::hasColumn('merchants', 'reseller_id')) {
            $rows = DB::table('reseller_merchant')
                ->select('merchant_id', DB::raw('MIN(reseller_id) as reseller_id'))
                ->groupBy('merchant_id')
                ->get();
            foreach ($rows as $row) {
                DB::table('merchants')
                    ->where('id', $row->merchant_id)
                    ->whereNull('reseller_id')
                    ->update(['reseller_id' => $row->reseller_id]);
            }
        }

        if (! Schema::hasTable('reseller_commissions')) {
            Schema::create('reseller_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
                $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
                $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
                $table->decimal('commission_amount', 14, 2);
                $table->decimal('reversed_amount', 14, 2)->default(0);
                $table->string('status', 32)->default('pending'); // pending, paid, reversed, partially_reversed
                $table->timestamps();

                $table->unique('transaction_id');
                $table->index(['reseller_id', 'status']);
                $table->index(['reseller_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reseller_commissions')) {
            Schema::dropIfExists('reseller_commissions');
        }
        if (Schema::hasTable('merchants') && Schema::hasColumn('merchants', 'reseller_id')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('reseller_id');
            });
        }
    }
};
