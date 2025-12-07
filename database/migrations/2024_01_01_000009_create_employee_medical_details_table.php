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
        Schema::create('employee_medical_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->unique()->index();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('genotype', 10)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('medications')->nullable();
            $table->text('disabilities')->nullable();
            $table->string('health_insurance_provider')->nullable();
            $table->string('health_insurance_number', 100)->nullable();
            $table->date('health_insurance_expiry')->nullable()->index();
            $table->text('emergency_medical_info')->nullable();
            $table->date('last_medical_checkup')->nullable();
            $table->date('next_medical_checkup')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_phone', 50)->nullable();
            $table->string('hospital_preference')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_medical_details');
    }
};
