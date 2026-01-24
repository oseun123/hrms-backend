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
            $table->decimal('skills_completion', 5, 2)->default(0)->after('certification_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profile_completeness', function (Blueprint $table) {
            $table->dropColumn('skills_completion');
        });
    }
};
