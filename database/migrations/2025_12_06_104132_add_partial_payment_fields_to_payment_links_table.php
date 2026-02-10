<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->boolean('allow_partial_payment')->default(false)->after('amount');
            $table->decimal('amount_paid', 15, 2)->default(0)->after('allow_partial_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropColumn(['allow_partial_payment', 'amount_paid']);
        });
    }
};
