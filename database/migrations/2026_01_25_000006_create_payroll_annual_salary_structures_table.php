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
        Schema::create('payroll_annual_salary_structures', function (Blueprint $link) {
            $link->id();
            $link->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $link->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $link->foreignId('pay_group_id')->constrained('payroll_pay_groups');
            $link->decimal('total_annual_gross', 15, 2);
            $link->decimal('total_annual_taxable', 15, 2);
            $link->decimal('total_annual_tax', 15, 2);
            $link->decimal('total_annual_pension_ee', 15, 2);
            $link->decimal('total_annual_pension_er', 15, 2);
            $link->decimal('total_annual_net', 15, 2);
            $link->enum('status', ['draft', 'active'])->default('draft');
            $link->timestamps();

            $link->index('tenant_id');
            $link->index('employee_id');
            $link->unique(['employee_id', 'status'], 'unique_active_structure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_annual_salary_structures');
    }
};
