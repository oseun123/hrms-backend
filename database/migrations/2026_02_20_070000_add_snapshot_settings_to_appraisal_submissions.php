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
        Schema::table('appraisal_submissions', function (Blueprint $table) {
            $table->integer('reviewer_levels')->default(2)->after('employee_id');
            $table->json('reviewer_config')->nullable()->after('reviewer_levels');
            $table->decimal('results_weight', 8, 2)->default(70.00)->after('reviewer_config');
            $table->decimal('competency_weight', 8, 2)->default(30.00)->after('results_weight');
            $table->integer('final_score_level')->default(2)->after('competency_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'reviewer_levels',
                'reviewer_config',
                'results_weight',
                'competency_weight',
                'final_score_level'
            ]);
        });
    }
};
