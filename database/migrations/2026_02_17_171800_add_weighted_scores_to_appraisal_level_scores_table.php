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
        Schema::table('appraisal_level_scores', function (Blueprint $table) {
            $table->decimal('goals_weighted_score', 5, 2)->nullable()->after('goals_score');
            $table->decimal('competency_weighted_score', 5, 2)->nullable()->after('competency_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_level_scores', function (Blueprint $table) {
            $table->dropColumn(['goals_weighted_score', 'competency_weighted_score']);
        });
    }
};
