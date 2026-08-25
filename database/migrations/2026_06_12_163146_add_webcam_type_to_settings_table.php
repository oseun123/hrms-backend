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
        Schema::table('attendance_work_schedule_settings', function (Blueprint $table) {
            $table->string('webcam_verification_type')->default('photo_proof')->after('enable_webcam'); // photo_proof or face_matching
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_work_schedule_settings', function (Blueprint $table) {
            $table->dropColumn('webcam_verification_type');
        });
    }
};
