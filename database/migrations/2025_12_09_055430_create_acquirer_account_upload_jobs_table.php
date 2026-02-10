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
        Schema::create('acquirer_account_upload_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name');
            $table->string('file_path');
            $table->string('payment_mode')->nullable();
            $table->json('bank_codes')->nullable(); // Array of selected bank codes
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('progress')->default(0); // 0-100
            $table->text('error')->nullable();
            $table->text('status_info')->nullable();
            $table->string('export_file_path')->nullable(); // Path to status/result file
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acquirer_account_upload_jobs');
    }
};
