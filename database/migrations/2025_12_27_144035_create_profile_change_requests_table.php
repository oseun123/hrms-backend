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
        Schema::create('profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('section', 50);
            $table->json('current_data')->nullable()->comment('Existing values before change');
            $table->json('proposed_data')->comment('Requested new values');
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null')->comment('User ID of HR who reviewed');
            $table->text('decline_reason')->nullable();
            $table->text('notes')->nullable()->comment('Employee notes/justification');
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('employee_id');
            $table->index('section');
            $table->index(['employee_id', 'section', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_change_requests');
    }
};
