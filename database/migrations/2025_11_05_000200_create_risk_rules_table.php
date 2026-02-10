<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('risk_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['velocity', 'amount_limit', 'geo_block', 'merchant_block', 'ip_block'])->default('velocity');
            $table->json('rule_config');
            $table->enum('action', ['block', 'alert', 'review'])->default('alert');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_rules');
    }
};

