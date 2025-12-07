<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('workflow_name');
            $table->string('module', 100)->index();
            $table->string('table_name', 100)->index();
            $table->enum('action_type', ['create', 'update', 'delete', 'all']);
            $table->boolean('is_enabled')->default(false)->index();
            $table->boolean('require_approval')->default(true);
            $table->decimal('auto_approve_threshold', 15, 2)->nullable();
            $table->integer('approval_levels')->default(1);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
