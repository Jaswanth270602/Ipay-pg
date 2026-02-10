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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company_name')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('default_currency', 3)->default('INR');
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->boolean('test_mode')->default(true);
            $table->decimal('fee_percentage', 5, 2)->default(2.50);
            $table->decimal('fee_flat', 10, 2)->default(0.30);
            $table->text('business_details')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('test_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};

