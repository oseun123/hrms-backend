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
        Schema::create('payroll_monthly_payments', function (Blueprint $link) {
            $link->id();
            $link->foreignId('batch_payment_id')->constrained('payroll_batch_payments', null, 'pm_payments_batch_id_fk')->onDelete('cascade');
            $link->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $link->decimal('gross_salary', 15, 2);
            $link->decimal('net_salary', 15, 2);
            $link->decimal('tax_amount', 15, 2);
            $link->decimal('pension_ee', 15, 2);
            $link->decimal('pension_er', 15, 2);
            $link->boolean('is_payslip_sent')->default(false);
            $link->timestamps();

            $link->index('batch_payment_id');
            $link->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_monthly_payments');
    }
};
