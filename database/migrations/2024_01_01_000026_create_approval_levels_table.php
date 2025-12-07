<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workflow_id')->index();
            $table->integer('level_number')->index();
            $table->string('level_name');
            $table->enum('approver_type', ['role', 'user', 'manager', 'department-head', 'custom']);
            $table->unsignedBigInteger('approver_role_id')->nullable();
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('can_skip')->default(false);
            $table->integer('timeout_hours')->nullable();
            $table->timestamps();

            $table->foreign('workflow_id')->references('id')->on('approval_workflows')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_levels');
    }
};
