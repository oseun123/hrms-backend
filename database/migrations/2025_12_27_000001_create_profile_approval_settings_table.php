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
        Schema::create('profile_approval_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('section', 50);
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();

            // Unique constraint: one setting per section per tenant
            $table->unique(['tenant_id', 'section'], 'unique_tenant_section');

            // Index for faster lookups
            $table->index('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_approval_settings');
    }
};
