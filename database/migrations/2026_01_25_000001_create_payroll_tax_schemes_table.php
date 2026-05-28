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
        Schema::create('payroll_tax_schemes', function (Blueprint $link) {
            $link->id();
            $link->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $link->string('name');
            $link->text('description')->nullable();
            $link->decimal('employee_pension_percentage', 5, 2)->default(0);
            $link->decimal('employer_pension_percentage', 5, 2)->default(0);
            $link->boolean('is_active')->default(true);
            $link->foreignId('created_by')->nullable()->constrained('users');
            $link->foreignId('updated_by')->nullable()->constrained('users');
            $link->timestamps();
            $link->softDeletes();

            $link->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_schemes');
    }
};
