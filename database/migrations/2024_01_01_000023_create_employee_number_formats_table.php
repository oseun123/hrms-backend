<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_number_formats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('format_name');
            $table->string('prefix', 50)->nullable();
            $table->boolean('include_year')->default(true);
            $table->enum('year_format', ['YYYY', 'YY'])->default('YYYY');
            $table->boolean('include_month')->default(false);
            $table->enum('month_format', ['MM', 'M'])->nullable();
            $table->string('separator', 10)->default('/');
            $table->integer('sequence_length')->default(3);
            $table->integer('current_sequence')->default(0);
            $table->enum('reset_sequence', ['never', 'yearly', 'monthly'])->default('yearly');
            $table->string('sample_format', 100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_formats');
    }
};
