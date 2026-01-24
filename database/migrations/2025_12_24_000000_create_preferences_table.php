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
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('category', 50)->index(); // e.g., 'display', 'language', 'organization'
            $table->string('key', 100)->index(); // e.g., 'theme_color', 'date_format'
            $table->text('value')->nullable(); // JSON-encoded value
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one preference per tenant/user/category/key combination
            $table->unique(['tenant_id', 'user_id', 'category', 'key'], 'preferences_unique');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
