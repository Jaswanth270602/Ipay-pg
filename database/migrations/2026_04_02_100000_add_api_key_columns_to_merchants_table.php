<?php

use App\Models\ApiKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('test_public_key', 128)->nullable()->after('test_mode');
            $table->string('test_secret_key', 128)->nullable()->after('test_public_key');
            $table->string('live_public_key', 128)->nullable()->after('test_secret_key');
            $table->string('live_secret_key', 128)->nullable()->after('live_public_key');

            $table->unique('test_public_key');
            $table->unique('live_public_key');
        });

        foreach (DB::table('merchants')->orderBy('id')->get() as $row) {
            $test = ApiKey::where('merchant_id', $row->id)->where('mode', 'test')->orderByDesc('id')->first();
            $live = ApiKey::where('merchant_id', $row->id)->where('mode', 'live')->orderByDesc('id')->first();

            DB::table('merchants')->where('id', $row->id)->update([
                'test_public_key' => $test?->key,
                'test_secret_key' => $test?->secret,
                'live_public_key' => $live?->key,
                'live_secret_key' => $live?->secret,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropUnique(['test_public_key']);
            $table->dropUnique(['live_public_key']);
            $table->dropColumn([
                'test_public_key',
                'test_secret_key',
                'live_public_key',
                'live_secret_key',
            ]);
        });
    }
};
