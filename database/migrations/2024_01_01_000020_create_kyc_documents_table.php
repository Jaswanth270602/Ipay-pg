<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('document_type'); // passport, driving_license, aadhaar, pan, business_license, etc.
            $table->string('document_number')->nullable();
            $table->string('file_path');
            $table->string('file_type')->nullable(); // image/pdf
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('merchant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};

