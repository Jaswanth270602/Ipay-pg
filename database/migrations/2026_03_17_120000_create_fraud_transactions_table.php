<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_transactions', function (Blueprint $table) {
            $table->id();
            // Can hold business txn id (e.g., TXN_xxx) even before a DB transaction row exists.
            $table->string('transaction_id')->nullable()->index();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->enum('decision', ['allow', 'review', 'block'])->index();
            $table->json('reasons')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_transactions');
    }
};
