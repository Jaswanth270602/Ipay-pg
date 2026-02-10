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
        Schema::create('merchant_tdr_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created the approval request');
            $table->unsignedBigInteger('merchant_id')->comment('Merchant ID');
            $table->string('merchant_name')->nullable()->comment('Merchant Name');
            $table->unsignedBigInteger('model_id')->nullable()->comment('Model ID (TDR ID)');
            $table->string('model_name')->nullable()->comment('Model Name');
            $table->string('operation')->nullable()->comment('Operation type: create, update, delete');
            $table->json('previous_changes')->nullable()->comment('Previous state/changes before modification');
            $table->json('changes')->nullable()->comment('Current changes/state');
            $table->enum('is_approved', ['pending', 'approved', 'rejected'])->default('pending')->comment('Approval status');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('User who approved/rejected');
            $table->timestamp('approved_at')->nullable()->comment('Date and time when approved/rejected');
            $table->text('approval_notes')->nullable()->comment('Notes from approver');
            $table->timestamps();

            $table->index('created_by');
            $table->index('merchant_id');
            $table->index('model_id');
            $table->index('is_approved');
            $table->index('approved_by');
            $table->index('created_at');

            if (Schema::hasTable('users')) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }
            if (Schema::hasTable('merchants')) {
                $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_tdr_approvals');
    }
};
