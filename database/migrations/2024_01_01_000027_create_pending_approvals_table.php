<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workflow_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('table_name', 100)->index();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->enum('action_type', ['create', 'update', 'delete']);
            $table->json('current_data')->nullable();
            $table->json('proposed_data');
            $table->text('change_summary')->nullable();
            $table->unsignedBigInteger('requested_by')->index();
            $table->timestamp('requested_at');
            $table->integer('current_level')->default(1)->index();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('workflow_id')->references('id')->on('approval_workflows')->onDelete('restrict');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_approvals');
    }
};
