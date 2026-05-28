<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename column if it exists (for consistency)
        if (Schema::hasColumn('leave_allowance_requests', 'included_in_batch_id')) {
            Schema::table('leave_allowance_requests', function (Blueprint $table) {
                $table->renameColumn('included_in_batch_id', 'batch_payment_id');
            });
        }

        // 2. Add monthly_payment_id and update status enum
        Schema::table('leave_allowance_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_allowance_requests', 'monthly_payment_id')) {
                $table->foreignId('monthly_payment_id')->after('id')->nullable()->constrained('payroll_monthly_payments')->onDelete('set null');
            }

            // To update enum in MySQL/MariaDB, we usually need a raw statement
            // adding 'paid' to the list
            DB::statement("ALTER TABLE leave_allowance_requests MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'paid') NOT NULL DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_allowance_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_allowance_requests', 'monthly_payment_id')) {
                $table->dropForeign(['monthly_payment_id']);
                $table->dropColumn('monthly_payment_id');
            }

            $table->renameColumn('batch_payment_id', 'included_in_batch_id');

            DB::statement("ALTER TABLE leave_allowance_requests MODIFY COLUMN status ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending'");
        });
    }
};
