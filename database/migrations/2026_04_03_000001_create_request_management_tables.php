<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('request_workflow_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_workflow_id')->constrained()->onDelete('cascade');
            $table->integer('level');
            $table->enum('approver_type', ['manager', 'team_lead', 'secondary_manager', 'hr', 'specific_employee']);
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('request_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['predefined', 'custom'])->default('custom');
            $table->string('template_key')->nullable()->index(); // e.g. cash_request
            $table->string('icon')->nullable();
            $table->json('fields')->nullable(); // For custom templates
            $table->boolean('is_active')->default(true);
            $table->foreignId('request_workflow_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('request_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('request_templates')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('reference_number')->unique();
            $table->json('form_data');
            $table->enum('status', ['pending', 'in_progress', 'approved', 'declined', 'cancelled'])->default('pending');
            $table->integer('current_level')->default(1);
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->integer('level');
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_approvals');
        Schema::dropIfExists('request_submissions');
        Schema::dropIfExists('request_templates');
        Schema::dropIfExists('request_workflow_levels');
        Schema::dropIfExists('request_workflows');
    }
};
