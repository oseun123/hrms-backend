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
        Schema::table('payroll_pay_groups', function (Blueprint $table) {
            $table->decimal('annual_gross', 15, 2)->after('name')->default(0);
            $table->dropColumn(['min_annual_gross', 'max_annual_gross']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_pay_groups', function (Blueprint $table) {
            $table->decimal('min_annual_gross', 15, 2)->after('name')->nullable();
            $table->decimal('max_annual_gross', 15, 2)->after('min_annual_gross')->nullable();
            $table->dropColumn('annual_gross');
        });
    }
};
