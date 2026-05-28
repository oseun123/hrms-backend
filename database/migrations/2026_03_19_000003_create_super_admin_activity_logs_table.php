<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->constrained('super_admins')->onDelete('cascade');
            $table->string('action'); // e.g. 'tenant.created', 'tenant.activated'
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable(); // e.g. 'App\Models\Tenant'
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('super_admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_activity_logs');
    }
};
