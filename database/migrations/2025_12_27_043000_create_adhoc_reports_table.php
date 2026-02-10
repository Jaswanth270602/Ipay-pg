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
        Schema::create('adhoc_reports', function (Blueprint $table) {
            $table->id();
            $table->string('adhoc_report_name')->comment('Name of the adhoc report');
            $table->text('adhoc_report_description')->nullable()->comment('Description of the adhoc report');
            $table->text('sql_query')->comment('SQL query for the report');
            $table->dateTime('adhoc_report_created_date')->useCurrent()->comment('Date when report was created');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created the report');
            $table->boolean('is_active')->default(true)->comment('Whether the report is active');
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->timestamps();
            
            $table->index('adhoc_report_name');
            $table->index('adhoc_report_created_date');
            $table->index('created_by');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adhoc_reports');
    }
};

