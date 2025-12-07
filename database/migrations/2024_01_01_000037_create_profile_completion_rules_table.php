<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_completion_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('rule_name');
            $table->enum('trigger_type', ['threshold', 'days-after-hire', 'manual', 'scheduled'])->index();
            $table->decimal('threshold_percentage', 5, 2)->nullable();
            $table->integer('days_after_hire')->nullable();
            $table->enum('notification_type', ['reminder', 'nudge', 'warning', 'final-notice']);
            $table->text('message_template');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_completion_rules');
    }
};
