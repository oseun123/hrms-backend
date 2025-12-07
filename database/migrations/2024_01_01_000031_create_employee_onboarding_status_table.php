<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_onboarding_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->unique()->index();
            $table->boolean('user_created')->default(false);
            $table->boolean('welcome_email_sent')->default(false);
            $table->timestamp('welcome_email_sent_at')->nullable();
            $table->boolean('password_reset_sent')->default(false);
            $table->timestamp('password_reset_sent_at')->nullable();
            $table->boolean('first_login_completed')->default(false);
            $table->timestamp('first_login_at')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->boolean('onboarding_completed')->default(false)->index();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_onboarding_status');
    }
};
