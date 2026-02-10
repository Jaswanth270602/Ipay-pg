<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('reason');
            $table->enum('status', ['open', 'needs_evidence', 'won', 'lost', 'closed'])->default('open');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('evidence_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('transaction_id');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};


