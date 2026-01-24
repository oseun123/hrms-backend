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
        Schema::create('leave_groups', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->string('name'); // e.g., Senior Staff, Junior Staff
            $blueprint->text('description')->nullable();
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        // Add leave_group_id to employee_employment_details
        Schema::table('employee_employment_details', function (Blueprint $table) {
            $table->foreignId('leave_group_id')->nullable()->constrained('leave_groups')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_employment_details', function (Blueprint $table) {
            $table->dropForeign(['leave_group_id']);
            $table->dropColumn('leave_group_id');
        });
        Schema::dropIfExists('leave_groups');
    }
};
