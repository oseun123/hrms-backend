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
        Schema::table('payroll_tax_schemes', function (Blueprint $table) {
            $table->boolean('apply_rent_relief')->default(false)->after('apply_cra');
            $table->decimal('rent_relief_max_amount', 15, 2)->default(500000)->after('apply_rent_relief');
            $table->decimal('rent_relief_percentage', 5, 2)->default(20.00)->after('rent_relief_max_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_tax_schemes', function (Blueprint $table) {
            $table->dropColumn(['apply_rent_relief', 'rent_relief_max_amount', 'rent_relief_percentage']);
        });
    }
};
