<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('section_id')->index();
            $table->string('field_name');
            $table->string('field_key', 100);
            $table->string('table_name', 100);
            $table->string('column_name', 100);
            $table->boolean('is_required')->default(false);
            $table->decimal('weight', 5, 2)->default(0);
            $table->string('validation_rule')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('section_id')->references('id')->on('profile_sections')->onDelete('cascade');
            $table->index(['table_name', 'column_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_fields');
    }
};
