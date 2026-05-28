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
        Schema::table('performance_settings', function (Blueprint $table) {
            $table->json('reviewer_config')->nullable()->after('reviewer_levels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_settings', function (Blueprint $table) {
            $table->dropColumn('reviewer_config');
        });
    }
};
