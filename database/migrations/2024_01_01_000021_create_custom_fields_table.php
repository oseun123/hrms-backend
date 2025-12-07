<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('field_name');
            $table->string('field_label');
            $table->enum('field_type', ['text', 'number', 'date', 'dropdown', 'checkbox', 'textarea']);
            $table->text('field_options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->text('validation_rules')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
