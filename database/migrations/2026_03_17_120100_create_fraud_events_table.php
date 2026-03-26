<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fraud_transaction_id')
                ->nullable()
                ->constrained('fraud_transactions')
                ->nullOnDelete();
            $table->string('rule_name')->index();
            $table->boolean('triggered')->default(false)->index();
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_events');
    }
};
