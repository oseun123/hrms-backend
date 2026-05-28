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
        Schema::create('payroll_pay_group_components', function (Blueprint $link) {
            $link->id();
            $link->foreignId('pay_group_id')->constrained('payroll_pay_groups')->onDelete('cascade');
            $link->foreignId('component_id')->constrained('payroll_salary_components');
            $link->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_pay_group_components');
    }
};
