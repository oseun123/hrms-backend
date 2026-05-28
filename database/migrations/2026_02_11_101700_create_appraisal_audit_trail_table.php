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
        Schema::create('appraisal_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('submission_id')->constrained('appraisal_submissions')->onDelete('cascade');
            $table->enum('action', ['created', 'submitted', 'reviewed', 'returned', 'accepted', 'rejected', 'completed']);
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('performed_at');
            $table->integer('from_level')->nullable();
            $table->integer('to_level')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['submission_id', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_audit_trail');
    }
};
