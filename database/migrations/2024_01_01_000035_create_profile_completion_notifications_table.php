<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_completion_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->enum('notification_type', ['reminder', 'nudge', 'warning', 'final-notice']);
            $table->text('message');
            $table->decimal('completion_percentage', 5, 2)->nullable();
            $table->timestamp('sent_at');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->boolean('action_taken')->default(false);
            $table->timestamp('action_taken_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('profile_sections')->onDelete('set null');
            $table->foreign('sent_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_completion_notifications');
    }
};
