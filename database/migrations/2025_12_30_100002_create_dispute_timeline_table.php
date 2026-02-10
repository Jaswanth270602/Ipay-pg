<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('disputes')->onDelete('cascade');
            $table->string('event'); // e.g., 'dispute_created', 'evidence_uploaded', 'status_changed', 'submitted'
            $table->text('notes')->nullable();
            $table->string('changed_by_type')->nullable(); // 'merchant', 'admin', 'system'
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->json('metadata')->nullable(); // Store additional event data
            $table->timestamp('created_at');

            $table->index('dispute_id');
            $table->index('event');
            $table->index('created_at');
            $table->index(['changed_by_type', 'changed_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_timeline');
    }
};

