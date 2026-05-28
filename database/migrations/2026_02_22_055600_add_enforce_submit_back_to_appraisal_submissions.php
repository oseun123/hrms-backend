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
            $table->boolean('enforce_submit_back')->default(true)->after('final_score_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_submissions', function (Blueprint $table) {
            $table->dropColumn('enforce_submit_back');
        });
    }
};
