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
        // For MySQL, we need to use a raw query to change the enum
        DB::statement("ALTER TABLE employee_addresses MODIFY COLUMN address_type ENUM('current', 'permanent', 'mailing', 'home', 'work')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE employee_addresses MODIFY COLUMN address_type ENUM('current', 'permanent', 'mailing')");
    }
};
