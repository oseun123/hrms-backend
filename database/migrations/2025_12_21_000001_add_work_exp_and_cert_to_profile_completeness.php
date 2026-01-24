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
        Schema::table('employee_profile_completeness', function (Blueprint $table) {
            $table->decimal('work_experience_completion', 5, 2)->default(0)->after('education_completion');
            $table->decimal('certification_completion', 5, 2)->default(0)->after('work_experience_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profile_completeness', function (Blueprint $table) {
            $table->dropColumn(['work_experience_completion', 'certification_completion']);
        });
    }
};
