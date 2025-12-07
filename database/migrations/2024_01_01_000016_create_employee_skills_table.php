<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('skill_id')->index();
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->decimal('years_of_experience', 4, 1)->nullable();
            $table->date('last_used')->nullable();
            $table->boolean('is_certified')->default(false);
            $table->string('certification_name')->nullable();
            $table->date('certification_date')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skills');
    }
};
