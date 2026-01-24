<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_approvals', function (Blueprint $table) {
            $table->timestamp('nudged_at')->nullable()->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('leave_approvals', function (Blueprint $table) {
            $table->dropColumn('nudged_at');
        });
    }
};
