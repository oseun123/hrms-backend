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
            $table->json('deliverables_snapshot')->nullable()->after('enforce_submit_back');
            $table->json('competencies_snapshot')->nullable()->after('deliverables_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_submissions', function (Blueprint $table) {
            $table->dropColumn(['deliverables_snapshot', 'competencies_snapshot']);
        });
    }
};
