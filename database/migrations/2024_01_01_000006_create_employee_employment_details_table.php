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
        Schema::create('employee_employment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->unique()->index();
            $table->string('work_email')->unique();
            $table->unsignedBigInteger('department_id')->index();
            $table->unsignedBigInteger('position_id')->index();
            $table->unsignedBigInteger('manager_id')->nullable()->index();
            $table->enum('employment_type', ['full-time', 'part-time', 'contract', 'intern', 'temporary']);
            $table->enum('employment_status', ['active', 'on-leave', 'suspended', 'terminated', 'resigned'])->index();
            $table->date('hire_date');
            $table->date('probation_end_date')->nullable();
            $table->enum('probation_status', ['pending', 'passed', 'failed', 'extended'])->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->enum('termination_type', ['voluntary', 'involuntary', 'retirement', 'end-of-contract'])->nullable();
            $table->text('termination_reason')->nullable();
            $table->integer('notice_period_days')->nullable();
            $table->string('work_location')->nullable();
            $table->string('work_schedule', 100)->nullable();
            $table->string('shift', 50)->nullable();
            $table->boolean('remote_work_eligible')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('restrict');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('restrict');
            $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_employment_details');
    }
};
