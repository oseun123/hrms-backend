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
        Schema::create('leave_approvals', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('leave_request_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('approver_id')->constrained('users')->onDelete('cascade');

            $blueprint->integer('level')->default(1);
            $blueprint->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $blueprint->text('comments')->nullable();
            $blueprint->timestamp('actioned_at')->nullable();

            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
