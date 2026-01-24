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
        Schema::create('leave_balances', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('employee_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('leave_type_id')->constrained()->onDelete('cascade');

            $blueprint->integer('year');
            $blueprint->decimal('entitlement', 8, 2)->default(0);
            $blueprint->decimal('carried_forward', 8, 2)->default(0);
            $blueprint->decimal('accrued', 8, 2)->default(0);
            $blueprint->decimal('used', 8, 2)->default(0);
            $blueprint->decimal('pending_approval', 8, 2)->default(0);

            $blueprint->timestamps();
            $blueprint->softDeletes();

            $blueprint->unique(['employee_id', 'leave_type_id', 'year'], 'unique_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
