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
        Schema::create('payroll_employee_pay_groups', function (Blueprint $link) {
            $link->id();
            $link->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $link->foreignId('pay_group_id')->constrained('payroll_pay_groups')->onDelete('cascade');
            $link->timestamps();

            $link->unique(['employee_id', 'pay_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_employee_pay_groups');
    }
};
