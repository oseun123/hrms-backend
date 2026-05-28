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
        Schema::table('employee_deliverables', function (Blueprint $table) {
            $table->string('snapshot_title')->nullable()->after('goal_id');
            $table->text('snapshot_description')->nullable()->after('snapshot_title');
            $table->string('snapshot_goal_type')->nullable()->after('snapshot_description');
        });

        Schema::table('employee_deliverable_details', function (Blueprint $table) {
            $table->text('snapshot_measure')->nullable()->after('measure_target_id');
            $table->text('snapshot_target')->nullable()->after('snapshot_measure');
            $table->string('snapshot_uom')->nullable()->after('snapshot_target');
            $table->decimal('snapshot_weightage', 5, 2)->nullable()->after('snapshot_uom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_deliverables', function (Blueprint $table) {
            $table->dropColumn(['snapshot_title', 'snapshot_description', 'snapshot_goal_type']);
        });

        Schema::table('employee_deliverable_details', function (Blueprint $table) {
            $table->dropColumn(['snapshot_measure', 'snapshot_target', 'snapshot_uom', 'snapshot_weightage']);
        });
    }
};
