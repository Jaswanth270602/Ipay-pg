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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('team_name')->nullable();
            $table->string('team_type')->default('partner')->comment('partner, internal, etc.');
            $table->string('organization_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_internal')->default(false);
            $table->string('referral_code')->unique()->nullable();
            $table->string('whitelabel_url')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('ref')->nullable()->comment('Reference code');
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index('email');
            $table->index('referral_code');
            $table->index('is_approved');
            $table->index('is_internal');
            $table->index('team_type');
            $table->index('registration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
