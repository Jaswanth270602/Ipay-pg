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
        Schema::create('merchant_registration_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');

            // Human readable description
            $table->string('key_description');

            // Actual registration key value (token)
            $table->string('registration_key')->unique();

            // Status: active / not_active
            $table->enum('status', ['active', 'not_active'])->default('not_active');

            // Optional IP restriction
            $table->string('ip_address')->nullable();

            // Copy flags
            $table->boolean('copy_merchant_params')->default(false);
            $table->boolean('copy_velocity_checks')->default(false);
            $table->boolean('copy_routing_randomize')->default(false);
            $table->boolean('copy_account_whitelisting')->default(false);

            $table->timestamps();

            $table->index('merchant_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_registration_keys');
    }
};


