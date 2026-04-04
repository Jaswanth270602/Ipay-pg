<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('split_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('split_transactions', 'account_holder_name')) {
                $table->string('account_holder_name')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('split_transactions', 'account_number')) {
                $table->string('account_number')->nullable();
            }
            if (! Schema::hasColumn('split_transactions', 'ifsc_code')) {
                $table->string('ifsc_code', 32)->nullable();
            }
            if (! Schema::hasColumn('split_transactions', 'split_type')) {
                $table->string('split_type', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('split_transactions', function (Blueprint $table) {
            foreach (['account_holder_name', 'account_number', 'ifsc_code', 'split_type'] as $col) {
                if (Schema::hasColumn('split_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
