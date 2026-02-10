<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->enum('alert_type', ['suspicious_pattern', 'chargeback_risk', 'velocity_anomaly', 'amount_anomaly', 'geo_anomaly'])->default('suspicious_pattern');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'investigating', 'resolved', 'false_positive'])->default('open');
            $table->text('description');
            $table->unsignedInteger('risk_score')->default(0);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('transaction_id');
            $table->index('status');
            $table->index('severity');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_alerts');
    }
};

