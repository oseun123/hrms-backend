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
        Schema::create('leave_adjustments', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('employee_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('leave_type_id')->constrained()->onDelete('cascade');

            $blueprint->decimal('adjustment_amount', 8, 2); // Positive for addition, negative for deduction
            $blueprint->enum('type', ['correction', 'bonus', 'penalty', 'manual_accrual'])->default('correction');
            $blueprint->text('reason')->nullable();
            $blueprint->foreignId('adjusted_by')->constrained('users')->onDelete('cascade');

            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_adjustments');
    }
};
