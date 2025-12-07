<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('section_name');
            $table->string('section_key', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('display_order')->default(0);
            $table->string('icon', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_sections');
    }
};
