<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('disputes')->onDelete('cascade');
            $table->enum('document_type', [
                'invoice',
                'delivery_proof',
                'communication',
                'refund_proof',
                'other'
            ]);
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_url')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index('dispute_id');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_evidence');
    }
};

