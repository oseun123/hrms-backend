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
        Schema::create('employee_contact_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->unique()->index();
            $table->string('personal_email')->nullable();
            $table->string('work_phone', 50)->nullable();
            $table->string('mobile_phone', 50)->nullable();
            $table->string('home_phone', 50)->nullable();
            $table->string('whatsapp_number', 50)->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('skype_id', 100)->nullable();
            $table->string('other_contact')->nullable();
            $table->enum('preferred_contact_method', ['work_email', 'personal_email', 'mobile', 'whatsapp'])->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_contact_details');
    }
};
