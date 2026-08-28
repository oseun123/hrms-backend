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
        DB::statement("ALTER TABLE `employee_employment_details` MODIFY `termination_type` ENUM('voluntary', 'involuntary', 'retirement', 'end-of-contract', 'mutual-agreement') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `employee_employment_details` MODIFY `termination_type` ENUM('voluntary', 'involuntary', 'retirement', 'end-of-contract') NULL");
    }
};
