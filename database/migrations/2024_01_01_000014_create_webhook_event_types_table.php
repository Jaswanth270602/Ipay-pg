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
        Schema::create('webhook_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique(); // e.g., payment.charged, payment.failed
            $table->string('name'); // Display name
            $table->text('description')->nullable();
            $table->string('category')->default('payment'); // payment, subscription, refund, etc.
            $table->boolean('enabled')->default(true);
            $table->json('payload_structure')->nullable(); // Example payload structure
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('event_key');
            $table->index('category');
            $table->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_event_types');
    }
};

