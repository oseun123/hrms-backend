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
        // 1. Create Wage Items table
        Schema::create('payroll_wage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Create Wage Item Components table
        Schema::create('payroll_wage_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wage_item_id')->constrained('payroll_wage_items')->onDelete('cascade');
            $table->foreignId('component_id')->constrained('payroll_salary_components')->onDelete('cascade');
            $table->decimal('amount_value', 15, 2);
            $table->timestamps();
        });

        // 3. Create Pay Group Wage Items (Many-to-Many)
        Schema::create('payroll_pay_group_wage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_group_id')->constrained('payroll_pay_groups')->onDelete('cascade');
            $table->foreignId('wage_item_id')->constrained('payroll_wage_items')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Update Employee Pay Groups to link specific Wage Item
        Schema::table('payroll_employee_pay_groups', function (Blueprint $table) {
            $table->foreignId('wage_item_id')->nullable()->after('pay_group_id')->constrained('payroll_wage_items');
        });

        // 5. Update Annual Structures to point to Wage Item
        Schema::table('payroll_annual_salary_structures', function (Blueprint $table) {
            $table->foreignId('wage_item_id')->nullable()->after('pay_group_id')->constrained('payroll_wage_items');
        });

        // 6. Cleanup: Remove direct link between Pay Group and Components if it exists
        // (Assuming we are fully transitioning to Wage Items based on user request)
        // Schema::dropIfExists('payroll_pay_group_components');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_annual_salary_structures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wage_item_id');
        });

        Schema::table('payroll_employee_pay_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wage_item_id');
        });

        Schema::dropIfExists('payroll_pay_group_wage_items');
        Schema::dropIfExists('payroll_wage_item_components');
        Schema::dropIfExists('payroll_wage_items');
    }
};
