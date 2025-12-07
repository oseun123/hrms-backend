<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_addresses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('employee_emergency_contacts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('employee_education', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('employee_dependents', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('employee_addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('employee_emergency_contacts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('employee_education', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('employee_dependents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
