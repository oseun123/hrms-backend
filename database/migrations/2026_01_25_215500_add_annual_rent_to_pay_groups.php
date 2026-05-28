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
            $table->decimal('annual_rent', 15, 2)->after('annual_gross')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_pay_groups', function (Blueprint $table) {
            $table->dropColumn('annual_rent');
        });
    }
};
