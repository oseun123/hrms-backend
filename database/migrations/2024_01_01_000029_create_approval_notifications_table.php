<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('pending_approval_id')->index();
            $table->unsignedBigInteger('recipient_id')->index();
            $table->enum('notification_type', ['approval-request', 'approved', 'rejected', 'escalated', 'reminder']);
            $table->text('message');
            $table->timestamp('sent_at');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('pending_approval_id')->references('id')->on('pending_approvals')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_notifications');
    }
};
