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
        Schema::create('appraisal_reviewer_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->integer('level_number');
            $table->enum('reviewer_type', ['employee', 'line_manager', 'custom'])->default('line_manager');
            $table->foreignId('custom_reviewer_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'level_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_reviewer_configs');
    }
};
