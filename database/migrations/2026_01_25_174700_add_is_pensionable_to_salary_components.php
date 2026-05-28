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
        Schema::table('payroll_salary_components', function (Blueprint $table) {
            $table->boolean('is_pensionable')->default(false)->after('show_on_payslip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_salary_components', function (Blueprint $table) {
            $table->dropColumn('is_pensionable');
        });
    }
};
