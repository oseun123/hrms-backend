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
        Schema::table('payroll_annual_salary_structures', function (Blueprint $link) {
            $link->decimal('total_annual_relief', 15, 2)->after('total_annual_taxable')->default(0);
        });

        Schema::table('payroll_monthly_payments', function (Blueprint $link) {
            $link->decimal('total_relief', 15, 2)->after('tax_amount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_annual_salary_structures', function (Blueprint $link) {
            $link->dropColumn('total_annual_relief');
        });

        Schema::table('payroll_monthly_payments', function (Blueprint $link) {
            $link->dropColumn('total_relief');
        });
    }
};
