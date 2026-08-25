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
        Schema::create('attendance_work_schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('17:00:00');
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();
            $table->integer('arrival_grace')->default(0);   // in minutes
            $table->integer('departure_grace')->default(0); // in minutes
            $table->json('work_days')->nullable();          // work days config json
            $table->json('overtime_days')->nullable();      // overtime config json
            $table->boolean('is_shift')->default(false);
            $table->boolean('enable_payroll')->default(false);
            
            // Web verification rules
            $table->boolean('enable_geofence')->default(true);
            $table->integer('geofence_radius')->default(100); // meters
            $table->boolean('enable_webcam')->default(false);  // Webcam Snapshot check
            
            $table->string('time_zone')->default('Africa/Lagos');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_work_schedule_settings');
    }
};
