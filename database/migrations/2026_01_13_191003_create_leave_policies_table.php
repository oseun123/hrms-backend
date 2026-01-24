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
        Schema::create('leave_policies', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('leave_group_id')->constrained()->onDelete('cascade');

            // Entitlement
            $blueprint->decimal('entitlement_days', 8, 2); // Days per year
            $blueprint->enum('accrual_frequency', ['yearly', 'monthly', 'on_hire', 'manual'])->default('yearly');
            $blueprint->boolean('is_prorated')->default(true);

            // Carry-Forward
            $blueprint->boolean('allow_carry_forward')->default(false);
            $blueprint->decimal('max_carry_forward_days', 8, 2)->default(0);
            $blueprint->integer('carry_forward_expiry_months')->nullable(); // e.g., expires in 3 months

            // Encashment
            $blueprint->boolean('allow_encashment')->default(false);
            $blueprint->decimal('max_encashment_days', 8, 2)->default(0);

            // Usage Rules
            $blueprint->integer('min_service_days')->default(0); // Eligible after X days
            $blueprint->integer('max_consecutive_days')->nullable();
            $blueprint->integer('notice_period_days')->default(0);
            $blueprint->boolean('allow_negative_balance')->default(false);
            $blueprint->decimal('max_negative_days', 8, 2)->default(0);

            // Holiday/Weekend Logic
            $blueprint->boolean('include_public_holidays')->default(false); // If holiday falls in leave, counts as leave
            $blueprint->boolean('include_weekends')->default(false);

            // Backdated Leave
            $blueprint->boolean('allow_backdated_leave')->default(false);
            $blueprint->integer('max_backdated_days')->default(0);

            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
            $blueprint->softDeletes();

            $blueprint->unique(['tenant_id', 'leave_type_id', 'leave_group_id'], 'unique_policy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
