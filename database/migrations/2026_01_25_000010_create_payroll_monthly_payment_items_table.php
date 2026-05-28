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
        Schema::create('payroll_monthly_payment_items', function (Blueprint $link) {
            $link->id();
            $link->foreignId('monthly_payment_id')->constrained('payroll_monthly_payments', null, 'pm_items_monthly_id_fk')->onDelete('cascade');
            $link->foreignId('component_id')->constrained('payroll_salary_components');
            $link->decimal('amount', 15, 2);
            $link->boolean('is_one_time')->default(false);
            $link->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_monthly_payment_items');
    }
};
