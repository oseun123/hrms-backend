<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profile_completeness', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->unique()->index();
            $table->decimal('overall_completion', 5, 2)->default(0)->index();
            $table->decimal('basic_info_completion', 5, 2)->default(0);
            $table->decimal('employment_completion', 5, 2)->default(0);
            $table->decimal('contact_completion', 5, 2)->default(0);
            $table->decimal('financial_completion', 5, 2)->default(0);
            $table->decimal('medical_completion', 5, 2)->default(0);
            $table->decimal('address_completion', 5, 2)->default(0);
            $table->decimal('emergency_contact_completion', 5, 2)->default(0);
            $table->decimal('education_completion', 5, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->boolean('is_complete')->default(false)->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profile_completeness');
    }
};
