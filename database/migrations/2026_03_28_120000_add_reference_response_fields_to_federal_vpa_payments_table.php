<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align federal_vpa_payments with list API / UI (reference_id, response flags).
     */
    public function up(): void
    {
        Schema::table('federal_vpa_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('federal_vpa_payments', 'reference_id')) {
                $table->string('reference_id')->nullable()->after('merchant_id');
            }
            if (!Schema::hasColumn('federal_vpa_payments', 'response_received')) {
                $table->boolean('response_received')->default(false)->after('file_path');
            }
            if (!Schema::hasColumn('federal_vpa_payments', 'response_data')) {
                $table->text('response_data')->nullable()->after('response_received');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('federal_vpa_payments', function (Blueprint $table) {
            $columns = [];
            foreach (['response_data', 'response_received', 'reference_id'] as $col) {
                if (Schema::hasColumn('federal_vpa_payments', $col)) {
                    $columns[] = $col;
                }
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
