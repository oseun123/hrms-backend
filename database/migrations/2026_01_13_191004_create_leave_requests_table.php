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
        Schema::create('leave_requests', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('employee_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('leave_type_id')->constrained()->onDelete('cascade');

            $blueprint->date('start_date');
            $blueprint->date('end_date');
            $blueprint->decimal('duration_days', 8, 2);
            $blueprint->text('reason')->nullable();
            $blueprint->string('attachment_path')->nullable();

            $blueprint->enum('status', ['pending', 'approved', 'declined', 'cancelled', 'partially_cancelled'])->default('pending');
            $blueprint->text('decline_reason')->nullable();

            $blueprint->timestamp('applied_at');
            $blueprint->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $blueprint->timestamp('cancelled_at')->nullable();

            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
