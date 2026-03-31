<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('merchant_vendors', 'password')) {
                $table->string('password')->nullable()->after('vendor_login_id');
            }
            if (!Schema::hasColumn('merchant_vendors', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        Schema::table('merchant_vendors', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_vendors', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
            if (Schema::hasColumn('merchant_vendors', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};

