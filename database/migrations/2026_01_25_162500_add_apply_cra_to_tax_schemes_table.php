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
        Schema::table('payroll_tax_schemes', function (Blueprint $col) {
            $col->boolean('apply_cra')->default(true)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_tax_schemes', function (Blueprint $col) {
            $col->dropColumn('apply_cra');
        });
    }
};
