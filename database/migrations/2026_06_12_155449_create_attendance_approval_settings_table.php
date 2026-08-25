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
        Schema::create('attendance_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('first_approver')->default('primary-line-manager'); // primary-line-manager, secondary-line-manager, system-hr
            $table->string('second_approver')->nullable();
            $table->string('third_approver')->nullable();
            $table->string('lateness_fee_approval')->default('primary-line-manager');
            $table->string('absenteeism_fee_approval')->default('primary-line-manager');
            $table->string('overtime_fee_approval')->default('primary-line-manager');
            $table->boolean('overtime_time_fee')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_approval_settings');
    }
};
