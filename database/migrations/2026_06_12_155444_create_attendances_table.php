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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('spent_time')->nullable(); // e.g. "8h:15m"
            $table->string('overtime')->nullable();   // e.g. "1h:30m"
            $table->enum('status', ['present', 'absent', 'late', 'flagged', 'pending', 'leave'])->default('present');
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_leave')->default(false);
            $table->boolean('is_late')->default(false);
            $table->string('shift_type')->default('morning');
            
            // Web validation data
            $table->string('ip_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('photo_proof_path')->nullable(); // Webcam photo path
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'user_id', 'date'], 'attendance_tenant_user_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
