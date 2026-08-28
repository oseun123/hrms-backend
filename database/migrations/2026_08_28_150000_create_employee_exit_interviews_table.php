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
        Schema::dropIfExists('employee_exit_interviews');
        
        Schema::create('employee_exit_interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('interviewer_id')->nullable()->index();
            $table->date('interview_date');
            
            // Primary & Secondary reasons
            $table->string('primary_reason_for_leaving')->nullable();
            $table->json('secondary_reasons')->nullable();
            
            // Ratings (1 to 5 scale)
            $table->unsignedTinyInteger('overall_experience_rating')->nullable();
            $table->unsignedTinyInteger('management_rating')->nullable();
            $table->unsignedTinyInteger('compensation_rating')->nullable();
            $table->unsignedTinyInteger('work_life_balance_rating')->nullable();
            $table->unsignedTinyInteger('growth_opportunities_rating')->nullable();
            $table->unsignedTinyInteger('culture_rating')->nullable();
            
            // Feedback notes
            $table->text('what_went_well')->nullable();
            $table->text('what_could_improve')->nullable();
            $table->text('additional_comments')->nullable();
            
            // Clearance and Handover Checklist
            $table->boolean('handover_completed')->default(false);
            $table->boolean('assets_returned')->default(false);
            $table->string('rehire_eligibility')->default('eligible'); // eligible, conditional, ineligible
            $table->text('rehire_notes')->nullable();
            
            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_exit_interviews');
    }
};
