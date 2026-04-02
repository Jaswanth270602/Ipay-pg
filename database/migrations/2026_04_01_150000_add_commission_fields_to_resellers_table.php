<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resellers')) {
            Schema::table('resellers', function (Blueprint $table) {
                if (! Schema::hasColumn('resellers', 'commission_type')) {
                    $table->enum('commission_type', ['percentage', 'fixed'])
                        ->default('percentage')
                        ->after('status');
                }
                if (! Schema::hasColumn('resellers', 'commission_value')) {
                    $table->decimal('commission_value', 12, 2)
                        ->default(0)
                        ->after('commission_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('resellers')) {
            Schema::table('resellers', function (Blueprint $table) {
                if (Schema::hasColumn('resellers', 'commission_value')) {
                    $table->dropColumn('commission_value');
                }
                if (Schema::hasColumn('resellers', 'commission_type')) {
                    $table->dropColumn('commission_type');
                }
            });
        }
    }
};

