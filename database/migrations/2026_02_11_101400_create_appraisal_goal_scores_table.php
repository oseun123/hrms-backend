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
        Schema::create('appraisal_goal_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('level_score_id')->constrained('appraisal_level_scores')->onDelete('cascade');
            $table->foreignId('employee_deliverable_id')->constrained()->onDelete('cascade');
            $table->foreignId('measure_target_id')->constrained('performance_measures_targets')->onDelete('cascade');
            $table->decimal('score', 5, 2);
            $table->text('comments')->nullable();
            $table->string('evidence_url')->nullable();
            $table->timestamps();

            $table->index(['level_score_id', 'employee_deliverable_id'], 'appr_goal_scores_level_deliverable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_goal_scores');
    }
};
