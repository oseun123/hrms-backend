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
        Schema::create('payroll_annual_salary_structure_items', function (Blueprint $link) {
            $link->id();
            $link->foreignId('annual_salary_structure_id')->constrained('payroll_annual_salary_structures', null, 'pass_items_structure_id_fk')->onDelete('cascade');
            $link->foreignId('component_id')->constrained('payroll_salary_components');
            $link->decimal('annual_amount', 15, 2);
            $link->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_annual_salary_structure_items');
    }
};
