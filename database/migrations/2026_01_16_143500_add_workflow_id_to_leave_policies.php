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
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->unsignedBigInteger('leave_workflow_id')->nullable()->after('leave_type_id');
            $table->foreign('leave_workflow_id')->references('id')->on('leave_workflows')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropForeign(['leave_workflow_id']);
            $table->dropColumn('leave_workflow_id');
        });
    }
};
