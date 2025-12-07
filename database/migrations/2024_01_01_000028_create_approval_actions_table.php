<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('pending_approval_id')->index();
            $table->integer('level_number')->index();
            $table->unsignedBigInteger('approver_id')->index();
            $table->enum('action', ['approved', 'rejected', 'delegated', 'escalated'])->index();
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('delegated_to')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->foreign('pending_approval_id')->references('id')->on('pending_approvals')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('delegated_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
