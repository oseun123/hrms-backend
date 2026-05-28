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
        Schema::table('payroll_wage_item_components', function (Blueprint $table) {
            $table->string('frequency')->default('monthly')->after('amount_value');
            $table->string('payment_month')->nullable()->after('frequency');
        });

        Schema::table('payroll_annual_salary_structure_items', function (Blueprint $table) {
            $table->string('frequency')->default('monthly')->after('annual_amount');
            $table->string('payment_month')->nullable()->after('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_wage_item_components', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'payment_month']);
        });

        Schema::table('payroll_annual_salary_structure_items', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'payment_month']);
        });
    }
};
