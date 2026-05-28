<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_allowance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('annual_structure_item_id')->nullable()->constrained('payroll_annual_salary_structure_items')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->string('leave_year', 20); // e.g., "2026" or "2026/2027"
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->foreignId('included_in_batch_id')->nullable()->constrained('payroll_batch_payments')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Ensure one allowance per employee per leave year
            $table->unique(['tenant_id', 'employee_id', 'leave_year'], 'unique_employee_leave_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_allowance_requests');
    }
};
