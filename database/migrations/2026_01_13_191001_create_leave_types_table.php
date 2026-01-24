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
        Schema::create('leave_types', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->string('name');
            $blueprint->string('code')->unique(); // e.g., AL, SL, CL
            $blueprint->text('description')->nullable();
            $blueprint->boolean('is_paid')->default(true);
            $blueprint->boolean('is_active')->default(true);
            $blueprint->boolean('requires_attachment')->default(false);
            $blueprint->boolean('is_seeded')->default(false);
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
