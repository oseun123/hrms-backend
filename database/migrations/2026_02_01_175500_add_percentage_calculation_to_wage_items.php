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
            $table->enum('calculation_type', ['fixed', 'percent_of_gross', 'percent_of_basic'])->default('fixed')->after('amount_value');
            $table->decimal('percent_value', 15, 2)->nullable()->after('calculation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_wage_item_components', function (Blueprint $table) {
            $table->dropColumn(['calculation_type', 'percent_value']);
        });
    }
};
