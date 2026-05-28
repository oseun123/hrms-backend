<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('domain');
            $table->enum('plan', ['starter', 'growth', 'enterprise'])->default('starter')->after('contact_email');
            $table->unsignedInteger('max_users')->nullable()->after('plan');
            $table->date('trial_ends_at')->nullable()->after('max_users');
            $table->text('notes')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['contact_email', 'plan', 'max_users', 'trial_ends_at', 'notes']);
        });
    }
};
