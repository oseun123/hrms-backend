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
        // Change change_type from ENUM to VARCHAR(255) (string)
        // Using raw SQL to avoid Doctrine DBAL dependency issues for enum modification
        DB::statement("ALTER TABLE employee_histories MODIFY COLUMN change_type VARCHAR(191) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to ENUM if needed, though this is risky if new values were added
        // Listing original values from create_employee_history_table.php
        DB::statement("ALTER TABLE employee_histories MODIFY COLUMN change_type ENUM('promotion', 'transfer', 'salary_change', 'position_change', 'department_change', 'status_change') NOT NULL");
    }
};
