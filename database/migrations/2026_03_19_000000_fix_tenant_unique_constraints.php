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
        // 1. Departments
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_code_unique');
            $table->unique(['tenant_id', 'code'], 'departments_tenant_code_unique');
        });

        // 2. Positions
        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique('positions_code_unique');
            $table->unique(['tenant_id', 'code'], 'positions_tenant_code_unique');
        });

        // 3. Levels
        Schema::table('levels', function (Blueprint $table) {
            $table->dropUnique('levels_code_unique');
            $table->unique(['tenant_id', 'code'], 'levels_tenant_code_unique');
        });

        // 4. Grades
        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique('grades_code_unique');
            $table->unique(['tenant_id', 'code'], 'grades_tenant_code_unique');
        });

        // 5. Leave Types
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropUnique('leave_types_code_unique');
            $table->unique(['tenant_id', 'code'], 'leave_types_tenant_code_unique');
        });

        // 6. Document Types
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropUnique('document_types_code_unique');
            $table->unique(['tenant_id', 'code'], 'document_types_tenant_code_unique');
        });

        // 7. Payroll Salary Components
        Schema::table('payroll_salary_components', function (Blueprint $table) {
            $table->dropUnique('payroll_salary_components_code_unique');
            $table->unique(['tenant_id', 'code'], 'payroll_salary_components_tenant_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Departments
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_tenant_code_unique');
            $table->unique('code', 'departments_code_unique');
        });

        // 2. Positions
        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique('positions_tenant_code_unique');
            $table->unique('code', 'positions_code_unique');
        });

        // 3. Levels
        Schema::table('levels', function (Blueprint $table) {
            $table->dropUnique('levels_tenant_code_unique');
            $table->unique('code', 'levels_code_unique');
        });

        // 4. Grades
        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique('grades_tenant_code_unique');
            $table->unique('code', 'grades_code_unique');
        });

        // 5. Leave Types
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropUnique('leave_types_tenant_code_unique');
            $table->unique('code', 'leave_types_code_unique');
        });

        // 6. Document Types
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropUnique('document_types_tenant_code_unique');
            $table->unique('code', 'document_types_code_unique');
        });

        // 7. Payroll Salary Components
        Schema::table('payroll_salary_components', function (Blueprint $table) {
            $table->dropUnique('payroll_salary_components_tenant_code_unique');
            $table->unique('code', 'payroll_salary_components_code_unique');
        });
    }
};
