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
        if (Schema::hasTable('employee_history') && !Schema::hasTable('employee_histories')) {
            Schema::rename('employee_history', 'employee_histories');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employee_histories') && !Schema::hasTable('employee_history')) {
            Schema::rename('employee_histories', 'employee_history');
        }
    }
};
