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
        Schema::create('payroll_batch_payments', function (Blueprint $link) {
            $link->id();
            $link->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $link->foreignId('pay_group_id')->constrained('payroll_pay_groups', null, 'pb_payments_group_id_fk');
            $link->integer('month');
            $link->integer('year');
            $link->enum('status', ['draft', 'authorized'])->default('draft');
            $link->timestamp('authorized_at')->nullable();
            $link->foreignId('authorized_by')->nullable()->constrained('users');
            $link->timestamps();

            $link->index('tenant_id');
            $link->unique(['pay_group_id', 'month', 'year'], 'pb_payments_gp_m_y_unq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_batch_payments');
    }
};
