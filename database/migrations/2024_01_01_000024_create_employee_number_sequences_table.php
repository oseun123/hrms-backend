<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('format_id')->index();
            $table->integer('year')->nullable();
            $table->integer('month')->nullable();
            $table->integer('last_sequence')->default(0);
            $table->timestamps();

            $table->foreign('format_id')->references('id')->on('employee_number_formats')->onDelete('cascade');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_sequences');
    }
};
