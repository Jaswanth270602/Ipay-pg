<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('risk_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->enum('event_type', ['rule_triggered', 'manual_review', 'auto_blocked'])->default('rule_triggered');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->index('rule_id');
            $table->index('merchant_id');
            $table->index('transaction_id');
            $table->index('resolved');
            $table->foreign('rule_id')->references('id')->on('risk_rules')->onDelete('set null');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_events');
    }
};

