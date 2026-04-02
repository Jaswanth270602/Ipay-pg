<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'reseller_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('reseller_id')->nullable()->after('merchant_id')->constrained('resellers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'reseller_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('reseller_id');
            });
        }
    }
};

