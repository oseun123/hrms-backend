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
        Schema::create('leave_year_end_processing', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->integer('from_year'); // e.g., 2025
            $blueprint->integer('to_year');   // e.g., 2026
            $blueprint->timestamp('processed_at');
            $blueprint->foreignId('processed_by')->constrained('users')->onDelete('cascade');
            $blueprint->integer('employees_processed')->default(0);
            $blueprint->json('summary')->nullable(); // Stats: total carried forward, etc.
            $blueprint->timestamps();

            $blueprint->unique(['tenant_id', 'from_year'], 'unique_year_processing');
            $blueprint->index(['tenant_id', 'to_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_year_end_processing');
    }
};
