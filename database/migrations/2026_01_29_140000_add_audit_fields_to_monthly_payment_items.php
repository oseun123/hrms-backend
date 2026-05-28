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
        Schema::table('payroll_monthly_payment_items', function (Blueprint $table) {
            // Add audit trail fields
            $table->text('reason')->nullable()->after('is_one_time');
            $table->foreignId('added_by')->nullable()->after('reason')->constrained('users')->onDelete('set null');
            $table->timestamp('added_at')->nullable()->after('added_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_monthly_payment_items', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropColumn(['reason', 'added_by', 'added_at']);
        });
    }
};
