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
        Schema::create('payroll_pay_groups', function (Blueprint $link) {
            $link->id();
            $link->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $link->string('name');
            $link->decimal('min_annual_gross', 15, 2);
            $link->decimal('max_annual_gross', 15, 2);
            $link->foreignId('tax_scheme_id')->constrained('payroll_tax_schemes');
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
        Schema::dropIfExists('payroll_pay_groups');
    }
};
