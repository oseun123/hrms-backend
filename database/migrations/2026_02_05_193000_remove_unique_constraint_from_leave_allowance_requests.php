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
        Schema::table('leave_allowance_requests', function (Blueprint $table) {
            $table->dropUnique('unique_employee_leave_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_allowance_requests', function (Blueprint $table) {
            $table->unique(['tenant_id', 'employee_id', 'leave_year'], 'unique_employee_leave_year');
        });
    }
};
