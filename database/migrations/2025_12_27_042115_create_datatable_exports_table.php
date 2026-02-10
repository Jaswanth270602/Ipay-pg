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
        Schema::create('datatable_exports', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_created')->comment('Date when export was created');
            $table->string('page_category')->nullable()->comment('Category/Page where export was initiated');
            $table->enum('queue_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->comment('Status of export queue');
            $table->enum('file_type', ['csv', 'xlsx', 'pdf', 'json'])->default('csv')->comment('Type of export file');
            $table->text('downloadable_url')->nullable()->comment('URL to download the exported file');
            $table->dateTime('expiry_time')->nullable()->comment('Time when file will expire');
            $table->string('file_name')->nullable()->comment('Name of the exported file');
            $table->string('file_path')->nullable()->comment('Path to the file on server');
            $table->text('export_params')->nullable()->comment('JSON parameters used for export');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created the export');
            $table->timestamps();
            
            $table->index('date_created');
            $table->index('page_category');
            $table->index('queue_status');
            $table->index('file_type');
            $table->index('created_by');
            $table->index('expiry_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datatable_exports');
    }
};
