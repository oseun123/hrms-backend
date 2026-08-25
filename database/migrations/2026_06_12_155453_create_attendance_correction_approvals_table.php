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
        Schema::create('attendance_correction_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('attendance_correction_request_id');
            $table->unsignedBigInteger('approver_id')->index();
            $table->unsignedInteger('level')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Custom short index name
            $table->index('attendance_correction_request_id', 'att_corr_req_idx');

            $table->foreign('attendance_correction_request_id', 'att_corr_req_foreign')
                ->references('id')
                ->on('attendance_correction_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_approvals');
    }
};
