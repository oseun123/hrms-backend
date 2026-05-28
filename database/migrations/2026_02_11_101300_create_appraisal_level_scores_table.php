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
        Schema::create('appraisal_level_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('submission_id')->constrained('appraisal_submissions')->onDelete('cascade');
            $table->integer('reviewer_level');
            $table->foreignId('reviewer_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('goals_score', 5, 2)->nullable();
            $table->decimal('competency_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'reviewer_level']);
            $table->index(['submission_id', 'reviewer_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_level_scores');
    }
};
