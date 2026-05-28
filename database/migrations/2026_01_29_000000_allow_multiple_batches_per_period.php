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
        Schema::table('payroll_batch_payments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('pb_payments_gp_m_y_unq');

            // Add a simple index for performance
            $table->index(['pay_group_id', 'month', 'year'], 'pb_payments_gp_m_y_idx');

            // Add batch_name to distinguish supplementary batches
            $table->string('batch_name')->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_batch_payments', function (Blueprint $table) {
            $table->dropColumn('batch_name');
            $table->dropIndex('pb_payments_gp_m_y_idx');
            $table->unique(['pay_group_id', 'month', 'year'], 'pb_payments_gp_m_y_unq');
        });
    }
};
