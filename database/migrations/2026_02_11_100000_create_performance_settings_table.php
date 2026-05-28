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
        Schema::create('performance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('cycle_type', ['monthly', 'quarterly', 'bi-annual', 'annual'])->default('annual');
            $table->integer('reviewer_levels')->default(2);
            $table->integer('final_score_level')->default(2);
            $table->decimal('results_weight', 5, 2)->default(70.00);
            $table->decimal('competency_weight', 5, 2)->default(30.00);
            $table->enum('goal_structure', ['simple', 'complex'])->default('simple');
            $table->boolean('enforce_submit_back')->default(false);
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_settings');
    }
};
