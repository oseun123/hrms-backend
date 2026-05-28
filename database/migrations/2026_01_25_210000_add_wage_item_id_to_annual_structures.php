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
        Schema::table('payroll_annual_salary_structures', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_annual_salary_structures', 'wage_item_id')) {
                $table->foreignId('wage_item_id')->after('pay_group_id')->constrained('payroll_wage_items');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_annual_salary_structures', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_annual_salary_structures', 'wage_item_id')) {
                $table->dropForeign(['wage_item_id']);
                $table->dropColumn('wage_item_id');
            }
        });
    }
};
