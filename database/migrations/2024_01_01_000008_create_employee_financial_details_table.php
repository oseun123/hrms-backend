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
        Schema::create('employee_financial_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->unique()->index();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('account_name')->nullable();
            $table->enum('account_type', ['savings', 'current', 'salary'])->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->string('iban', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->string('tax_status', 100)->nullable();
            $table->string('social_security_number', 100)->nullable();
            $table->string('pension_number', 100)->nullable();
            $table->string('insurance_number', 100)->nullable();
            $table->decimal('current_salary', 15, 2)->nullable();
            $table->string('salary_currency', 10)->default('USD');
            $table->enum('payment_frequency', ['monthly', 'bi-weekly', 'weekly', 'daily'])->nullable();
            $table->enum('payment_method', ['bank-transfer', 'cash', 'check', 'mobile-money'])->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_financial_details');
    }
};
