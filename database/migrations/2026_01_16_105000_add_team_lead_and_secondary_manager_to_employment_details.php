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
        Schema::table('employee_employment_details', function (Blueprint $table) {
            $table->unsignedBigInteger('team_lead_id')->nullable()->after('manager_id');
            $table->unsignedBigInteger('secondary_manager_id')->nullable()->after('team_lead_id');

            $table->foreign('team_lead_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('secondary_manager_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_employment_details', function (Blueprint $table) {
            $table->dropForeign(['team_lead_id']);
            $table->dropForeign(['secondary_manager_id']);
            $table->dropColumn(['team_lead_id', 'secondary_manager_id']);
        });
    }
};
