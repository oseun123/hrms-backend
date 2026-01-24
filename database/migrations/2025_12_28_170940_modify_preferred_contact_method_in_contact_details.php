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
        // Change preferred_contact_method from ENUM to VARCHAR(191)
        DB::statement("ALTER TABLE employee_contact_details MODIFY COLUMN preferred_contact_method VARCHAR(191) DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to ENUM with original values
        DB::statement("ALTER TABLE employee_contact_details MODIFY COLUMN preferred_contact_method ENUM('work_email', 'personal_email', 'mobile', 'whatsapp') DEFAULT NULL");
    }
};
