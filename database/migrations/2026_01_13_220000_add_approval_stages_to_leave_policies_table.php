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
            $table->integer('approval_stages')->default(1)->after('is_active');
            $table->boolean('requires_hr_approval')->default(false)->after('approval_stages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropColumn(['approval_stages', 'requires_hr_approval']);
        });
    }
};
