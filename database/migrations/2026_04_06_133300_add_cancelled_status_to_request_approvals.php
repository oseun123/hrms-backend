<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL, we need to use a raw query to update an ENUM column safely
        // Alternatively, use change() if the doctrine/dbal is installed, 
        // but raw SQL is more reliable for ENUMs in Laravel.
        DB::statement("ALTER TABLE request_approvals MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE request_approvals MODIFY COLUMN status ENUM('pending', 'approved', 'declined') DEFAULT 'pending'");
    }
};
