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
        Schema::create('leave_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('leave_workflow_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('leave_workflow_id');
            $table->integer('level');
            $table->string('approver_type'); // manager, team_lead, secondary_manager, hr, specific_employee
            $table->unsignedBigInteger('approver_id')->nullable(); // For specific_employee
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('leave_workflow_id')->references('id')->on('leave_workflows')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('employees')->onDelete('set null');

            $table->unique(['leave_workflow_id', 'level'], 'workflow_level_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_workflow_levels');
        Schema::dropIfExists('leave_workflows');
    }
};
