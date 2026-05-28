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
        Schema::create('payroll_salary_components', function (Blueprint $link) {
            $link->id();
            $link->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $link->string('name');
            $link->string('code')->unique();
            $link->enum('type', ['earning', 'deduction']);
            $link->enum('category', ['fixed', 'variable', 'statutory', 'voluntary']);
            $link->boolean('is_taxable')->default(true);
            $link->boolean('is_tax_deductible')->default(false);
            $link->enum('calculation_type', ['fixed', 'percentage', 'formula']);
            $link->decimal('amount_value', 15, 2)->default(0);
            $link->text('formula')->nullable();
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
        Schema::dropIfExists('payroll_salary_components');
    }
};
